<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $doctor = $_POST['doctor'];
    $description = $_POST['description'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = $_POST['status'];

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($doctor) || empty($description) || empty($appointment_date) || empty($appointment_time) || empty($status)) {
        echo "All fields are required!";
        exit;
    }

    // Insert into database (assuming 'users' table has these columns)
    $sql = "INSERT INTO `appointments` (`id`, `patient_name`, `patient_email`, `doctor_name`, `description`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, current_timestamp())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $full_name, $email, $doctor, $description, $appointment_date, $appointment_time, $status);

    if ($stmt->execute()) {
        $stmt->close();

        // send notification email to patient
        require_once __DIR__ . '/email_helper.php';

        $to = $email; // patient email from POST
        $subject = "Appointment booked at Hospital";
        $body  = "Hello " . $full_name . ",\n\n";
        $body .= "Your appointment has been booked with Dr. " . ($doctor_name ?? $doctor_raw) . ".\n";
        $body .= "Date: " . $appointment_date . "\n";
        $body .= "Time: " . $appointment_time . "\n\n";
        $body .= "Description: " . $description . "\n\n";
        $body .= "If you need to change or cancel this appointment, please contact us or do so in the patients portal.\n\n";
        $body .= "Regards,\nHospital Management System";

        // best-effort send, log if it fails
        if (!sendEmail($to, $subject, $body)) {
            error_log("Failed to send appointment email to {$to}");
        }

        // redirect back to admin appointments page
        header("Location: ../frontend/admin/appointments.php?added=1");
        exit;
    } else {
        $err = addslashes($stmt->error);
        $stmt->close();
        echo "<script>alert('Error: " . $err . "'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
?>

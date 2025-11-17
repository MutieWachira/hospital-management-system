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

        // resolve doctor name/email (support doctor id or full name)
        $doctor_name = trim($doctor);
        $doctor_email = null;

        if (ctype_digit($doctor)) {
            $did = (int)$doctor;
            $dstmt = $conn->prepare("SELECT full_name, email FROM doctors WHERE id = ? LIMIT 1");
            if ($dstmt) {
                $dstmt->bind_param("i", $did);
                $dstmt->execute();
                $dres = $dstmt->get_result();
                if ($drow = $dres->fetch_assoc()) {
                    $doctor_name = $drow['full_name'] ?? $doctor_name;
                    $doctor_email = $drow['email'] ?? null;
                }
                $dstmt->close();
            }
        } else {
            // doctor posted as name — try to find email by exact name
            $dstmt = $conn->prepare("SELECT email FROM doctors WHERE full_name = ? LIMIT 1");
            if ($dstmt) {
                $dstmt->bind_param("s", $doctor_name);
                $dstmt->execute();
                $dres = $dstmt->get_result();
                if ($drow = $dres->fetch_assoc()) {
                    $doctor_email = $drow['email'] ?? null;
                }
                $dstmt->close();
            }
        }

        // send notification email to patient
        require_once __DIR__ . '/email_helper.php';

        $to = $email; // patient email from POST
        $subject = "Appointment booked at Hospital";
        $body  = "Hello " . $full_name . ",\n\n";
        $body .= "Your appointment has been booked with Dr. " . ($doctor_name ?: $doctor) . ".\n";
        $body .= "Date: " . $appointment_date . "\n";
        $body .= "Time: " . $appointment_time . "\n\n";
        $body .= "Description: " . $description . "\n\n";
        $body .= "If you need to change or cancel this appointment, please contact us or do so in the patients portal.\n\n";
        $body .= "Regards,\nHospital Management System";

        if (!sendEmail($to, $subject, $body)) {
            error_log("Failed to send appointment email to patient: {$to}");
        }

        // notify doctor if we have an email
        if (!empty($doctor_email)) {
            $dsub = "New appointment scheduled with " . $full_name;
            $dbody = "Hello Dr. " . $doctor_name . ",\n\n";
            $dbody .= "A new appointment has been scheduled with the following details:\n";
            $dbody .= "Patient: " . $full_name . " (" . $email . ")\n";
            $dbody .= "Date: " . $appointment_date . "\n";
            $dbody .= "Time: " . $appointment_time . "\n";
            $dbody .= "Description: " . $description . "\n\n";
            $dbody .= "Regards,\nHospital Management System";

            if (!sendEmail($doctor_email, $dsub, $dbody)) {
                error_log("Failed to send appointment email to doctor: {$doctor_email}");
            }
        } else {
            error_log("Doctor email not found for doctor identifier: " . $doctor);
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

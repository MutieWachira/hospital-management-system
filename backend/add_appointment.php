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
        echo "Appointment added successfully!";
        header("Location: ../frontend/admin/appointments.php"); // Redirect to appointment list
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

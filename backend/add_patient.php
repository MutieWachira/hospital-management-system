<?php
// Start session
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $doctor = $_POST['doctor'];

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($age) || empty($gender) || empty($blood_group) || empty($phone) || empty($address) || empty($doctor)) {
        echo "All fields are required!";
        exit;
    }

    // Insert into database (assuming 'users' table has these columns)
    $sql = "INSERT INTO `patients` (`id`, `full_name`, `email`, `age`, `gender`, `blood_group`, `phone`, `address`, `doctor`, `role`, `created_at`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'patient', current_timestamp());";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssss", $full_name, $email, $age, $gender, $blood_group, $phone, $address, $doctor);

    if ($stmt->execute()) {
        echo "Patient added successfully!";
        header("Location: ../frontend/admin/patients.php"); // Redirect to patients list
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

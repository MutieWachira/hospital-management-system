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
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $doctor = $_POST['doctor'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($full_name) || empty($email) || empty($date_of_birth) || empty($gender) || empty($blood_group) || empty($phone) || empty($address) || empty($doctor) || empty($password) || empty($confirm_password)) {
        echo "All fields are required!";
        exit;
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $sql = "INSERT INTO `patients` 
            (`id`, `full_name`, `email`, `d_o_b`, `gender`, `blood_group`, `phone`, `address`, `doctor`, `password`, `role`, `created_at`) 
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'patient', CURRENT_TIMESTAMP())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $full_name, $email, $date_of_birth, $gender, $blood_group, $phone, $address, $doctor, $hashedPassword);

    if ($stmt->execute()) {
        echo "<script>alert('Patient added successfully!'); window.location.href='../frontend/admin/patients.php';</script>";
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $department = $_POST['department'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    // ✅ Validate required fields
    if (
        empty($full_name) || empty($email) || empty($password) || empty($confirm_password) ||
        empty($age) || empty($gender) || empty($department) ||
        empty($phone) || empty($address) || empty($status)
    ) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit;
    }

    // ✅ Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // ✅ Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        echo "<script>alert('Email already exists!'); window.history.back();</script>";
        exit;
    }
    $check_email->close();

    // ✅ Insert into database
    $sql = "INSERT INTO `doctors` 
            (`full_name`, `email`, `password`, `age`, `gender`, `department`, `phone`, `address`, `status`, `role`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'doctor', current_timestamp())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisssss", $full_name, $email, $hashed_password, $age, $gender, $department, $phone, $address, $status);

    if ($stmt->execute()) {
        echo "<script>alert('Doctor added successfully!'); window.location='../frontend/admin/doctor.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
?>

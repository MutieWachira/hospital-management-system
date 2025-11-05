<?php
// Start session
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';
require_once 'email_helper.php'; // ✅ Include email helper

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $doctor = $_POST['doctor'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (
        empty($full_name) || empty($email) || empty($date_of_birth) || 
        empty($gender) || empty($blood_group) || empty($phone) || 
        empty($address) || empty($doctor) || empty($password) || 
        empty($confirm_password)
    ) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit;
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $check_email->close();
        echo "<script>alert('Email already exists!'); window.history.back();</script>";
        exit;
    }
    $check_email->close();

    // Insert into database
    $sql = "INSERT INTO `patients` 
            (`id`, `full_name`, `email`, `d_o_b`, `gender`, `blood_group`, `phone`, `address`, `doctor`, `password`, `role`, `created_at`) 
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'patient', CURRENT_TIMESTAMP())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $full_name, $email, $date_of_birth, $gender, $blood_group, $phone, $address, $doctor, $hashedPassword);

    if ($stmt->execute()) {
        // ✅ Send Welcome Email to Patient
        $subject = "Welcome to Hospital Management System (HMS)";
        $message = "
        Dear $full_name,

        Your patient account has been successfully created in the Hospital Management System.

        You can now log in using your registered email:
        Email: $email
        Password: $password
        
        Please remember to keep your password secure and update your password after logging in.

        Regards,
        HMS Administration Team
        ";

        sendEmail($email, $subject, $message);

        // ✅ Optional: Notify the assigned doctor
        $doctorEmailQuery = $conn->prepare("SELECT email FROM doctors WHERE full_name = ?");
        $doctorEmailQuery->bind_param("s", $doctor);
        $doctorEmailQuery->execute();
        $doctorEmailResult = $doctorEmailQuery->get_result();
        if ($doctorEmail = $doctorEmailResult->fetch_assoc()['email'] ?? '') {
            $doctorSubject = "New Patient Assigned to You";
            $doctorMessage = "
            Dear Dr. $doctor,

            A new patient has been assigned to you in the HMS system.

            Patient Name: $full_name
            Email: $email
            Blood Group: $blood_group
            Phone: $phone

            Please log in to your Doctor Panel for more details.

            Regards,
            HMS Administration Team
            ";
            sendEmail($doctorEmail, $doctorSubject, $doctorMessage);
        }
        $doctorEmailQuery->close();

        echo "<script>alert('Patient added successfully and email notification sent!'); window.location.href='../frontend/admin/patients.php';</script>";
        exit;
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>

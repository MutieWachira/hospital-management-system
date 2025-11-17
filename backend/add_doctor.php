<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';
require_once 'email_helper.php';


// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $department = $_POST['department'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    // If admin did not enter a password, default to doctor's first name (sanitized)
    if ($password === '') {
        $firstName = 'doctor' . rand(100,999);
        if ($full_name !== '') {
            $parts = preg_split('/\s+/', $full_name);
            $fn = $parts[0] ?? $full_name;
            // keep only alphanumeric characters for default password
            $fn = preg_replace('/[^A-Za-z0-9]/', '', $fn);
            if ($fn !== '') {
                $firstName = $fn;
            }
        }
        $password = $firstName;
        $confirm_password = $password;
        $usingDefaultPassword = true;
    } else {
        $usingDefaultPassword = false;
    }

    //  Validate required fields
    if (
        empty($full_name) || empty($email) || empty($password) || empty($confirm_password) ||
        empty($date_of_birth) || empty($gender) || empty($department) ||
        empty($phone) || empty($address) || empty($status)
    ) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit;
    }

    //  Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    //  Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    //  Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
    if (!$check_email) {
        echo "<script>alert('Database error: " . addslashes($conn->error) . "'); window.history.back();</script>";
        exit;
    }
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $check_email->close();
        echo "<script>alert('Email already exists!'); window.history.back();</script>";
        exit;
    }
    $check_email->close();

    //  Insert into database
    $sql = "INSERT INTO `doctors` 
            (`full_name`, `email`, `password`, `d_o_b`, `gender`, `department`, `phone`, `address`, `status`, `role`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'doctor', current_timestamp())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "<script>alert('Prepare failed: " . addslashes($conn->error) . "'); window.history.back();</script>";
        exit;
    }

    // all nine fields are strings (date stored as 'YYYY-MM-DD')
    $stmt->bind_param("sssssssss", $full_name, $email, $hashed_password, $date_of_birth, $gender, $department, $phone, $address, $status);

    if ($stmt->execute()) {
        $stmt->close();
         // ✉️ Email notification to the doctor
    $subject = "Welcome to Hospital Management System (HMS)";
    $message = "
    Dear Dr. $full_name,

    Your doctor account has been successfully created on the Hospital Management System.

    You can now log in using your registered email:
    Email: $email
    Temporary Password: $password
    
    Please keep your password confidential and change it after your first login.

    Regards,
    HMS Administration Team
    ";

    sendEmail($email, $subject, $message);

    // (Optional) ✉️ Notify admin as well
    $adminEmail = "admin@hospital.com"; // replace with your actual admin email
    $adminMessage = "A new doctor account has been created.\n\nDoctor: $full_name\nEmail: $email\nDepartment: $department";
    sendEmail($adminEmail, "New Doctor Added - HMS", $adminMessage);

    echo "<script>alert('Doctor added successfully and email sent!'); window.location='../frontend/admin/doctor.php';</script>";
    exit;
    } else {
        $err = addslashes($stmt->error);
        $stmt->close();
        echo "<script>alert('Error: " . $err . "'); window.history.back();</script>";
    }
}

$conn->close();
?>

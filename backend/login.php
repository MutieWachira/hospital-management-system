<?php
session_start();
include('db_connect.php');

// Check if login form was submitted
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Sanitize inputs
    $email = mysqli_real_escape_string($conn, $email);
    $password = mysqli_real_escape_string($conn, $password);

    // Fetch user from database
    $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Redirect based on user role
        if ($user['role'] == 'admin') {
            header("Location: ../frontend/admin/index.html");
        } elseif ($user['role'] == 'doctor') {
            header("Location: ../frontend/doctor/doctor-dashboard.html");
        } elseif ($user['role'] == 'patient') {
            header("Location: ../frontend/patient/patient-dashboard.html");
        }
        exit();
    } else {
        echo "<script>alert('Invalid email or password!'); window.location.href='../frontend/login.html';</script>";
    }
}
?>

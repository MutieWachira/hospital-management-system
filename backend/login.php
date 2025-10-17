<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../backend/db_connect.php'; // Database connection

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        echo "<script>alert('Please fill in all fields'); window.history.back();</script>";
        exit;
    }

    /**
     * Check user login for a specific table
     * Supports both old MD5 and new password_hash() methods
     * Automatically upgrades old MD5 passwords to password_hash()
     */
    function checkUser($conn, $table, $email, $password) {
        $query = "SELECT * FROM $table WHERE email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $storedHash = $user['password'];

            // Case 1: password_hash() format (bcrypt/argon2)
            if (password_verify($password, $storedHash)) {
                return $user;
            }

            // Case 2: Old MD5 hash (upgrade it)
            if ($storedHash === md5($password)) {
                // Generate new secure hash
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE $table SET password = ? WHERE id = ?");
                $update->bind_param("si", $newHash, $user['id']);
                $update->execute();

                return $user;
            }
        }
        return false;
    }

    // === CHECK ROLES ===

    // Admin
    if ($user = checkUser($conn, 'admin', $email, $password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = $user['full_name'] ?? 'Admin';
        header("Location: ../frontend/admin/index.php");
        exit;
    }

    // Doctor
    if ($user = checkUser($conn, 'doctors', $email, $password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'doctor';
        $_SESSION['name'] = $user['full_name'] ?? 'Doctor';
        header("Location: ../frontend/doctor/doctor-dashboard.php");
        exit;
    }

    // Patient
    if ($user = checkUser($conn, 'patients', $email, $password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = 'patient';
        $_SESSION['name'] = $user['full_name'] ?? 'Patient';
        header("Location: ../frontend/patient/patient-dashboard.php");
        exit;
    }

    // If login fails
    echo "<script>alert('Invalid email or password'); window.history.back();</script>";
}
?>

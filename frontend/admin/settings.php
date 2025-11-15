<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../../backend/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
  exit;
}

$admin_id = $_SESSION['user_id'];

//  Fetch admin details
$query = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$query->bind_param("i", $admin_id);
$query->execute();
$result = $query->get_result();
$admin = $result->fetch_assoc();

// Handle form submission
if (isset($_POST['update'])) {
  $full_name = trim($_POST['full_name']);
  $email = trim($_POST['email']);
  $current_password = $_POST['current_password'];
  $new_password = $_POST['password'];

  // Check for empty fields
  if (empty($full_name) || empty($email)) {
    echo "<script>alert('Please fill in all required fields'); window.history.back();</script>";
    exit;
  }

  // Only verify current password if changing password
  if (!empty($new_password)) {
    if (empty($current_password) || md5($current_password) !== $admin['password']) {
      echo "<script>alert('Incorrect current password'); window.history.back();</script>";
      exit;
    }
    $final_password = md5($new_password);
  } else {
    $final_password = $admin['password'];
  }

  // Update admin record
  $update = $conn->prepare("UPDATE admin SET full_name=?, email=?, password=? WHERE id=?");
  $update->bind_param("sssi", $full_name, $email, $final_password, $admin_id);

  if ($update->execute()) {
    header("Location: settings.php?success=1");
    exit;
  } else {
    echo "<script>alert('Error updating profile. Please try again.');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Settings - HMS</title>
  <link rel="stylesheet" href="css/settings.css">
</head>
<body>
  <!-- ===== Sidebar ===== -->
  <aside class="sidebar">
    <h2>HMS Admin</h2>
    <ul>
      <li><a href="index.php">Dashboard</a></li>
      <li><a href="patients.php">Patients</a></li>
      <li><a href="doctor.php">Doctors</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="settings.php" class="active">Profile</a></li>
      <li><a href="../../backend/logout.php">Logout</a></li>
    </ul>
  </aside>

  <!-- ===== Main Section ===== -->
  <main class="main">
    <div class="topbar">
      <h2>Admin Settings</h2>
    </div>

    <section class="settings-section">
      <h3>Update Admin Profile</h3>

      <?php if (isset($_GET['success'])): ?>
        <div class="success-msg"> Profile updated successfully!</div>
      <?php endif; ?>

      <form method="POST" class="settings-form">
        <div class="form-group">
          <label>Full Name:</label>
          <input type="text" name="full_name" 
                 value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" 
                 required>
        </div>

        <div class="form-group">
          <label>Email:</label>
          <input type="email" name="email" 
                 value="<?= htmlspecialchars($admin['email'] ?? '') ?>" 
                 required>
        </div>

        <div class="form-group">
          <label>Current Password:</label>
          <input type="password" name="current_password" 
                 placeholder="Enter current password">
        </div>

        <div class="form-group">
          <label>New Password (leave blank to keep current):</label>
          <input type="password" name="password" 
                 placeholder="Enter new password (optional)">
        </div>

        <button type="submit" name="update" class="save-btn">Save Changes</button>
      </form>
    </section>
  </main>
</body>
</html>

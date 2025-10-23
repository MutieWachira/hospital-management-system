<?php
// ✅ Enable errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
session_start();

// ✅ Require patient login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header('Location: ../../frontend/login.html');
  exit;
}

$patient_id = $_SESSION['user_id'];

// ✅ Fetch patient info
$query = "SELECT full_name, email, phone, address FROM patients WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// ✅ Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['updateProfile'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $address = $_POST['address'];
  $dob = $_POST['dob'];

  $update = $conn->prepare("UPDATE patients SET full_name=?, email=?, phone=?, address=?, dob=? WHERE id=?");
  $update->bind_param("sssssi", $name, $email, $phone, $address, $dob, $patient_id);

  if ($update->execute()) {
    $message = "Profile updated successfully!";
  } else {
    $message = "Error updating profile: " . $conn->error;
  }
}

// ✅ Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['changePassword'])) {
  $current = $_POST['currentPassword'];
  $new = $_POST['newPassword'];
  $confirm = $_POST['confirmPassword'];

  if ($new !== $confirm) {
    $message = "❌ New passwords do not match!";
  } else {
    $check = $conn->prepare("SELECT password FROM patients WHERE id=?");
    $check->bind_param("i", $patient_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if (!password_verify($current, $res['password'])) {
      $message = "❌ Incorrect current password!";
    } else {
      $hashed = password_hash($new, PASSWORD_DEFAULT);
      $updatePass = $conn->prepare("UPDATE patients SET password=? WHERE id=?");
      $updatePass->bind_param("si", $hashed, $patient_id);
      $updatePass->execute();
      $message = "✅ Password updated successfully!";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patient Profile - HMS</title>
  <link rel="stylesheet" href="css/patient-profile.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Patient Panel</h2>
      <ul>
        <li><a href="patient-dashboard.php">Dashboard</a></li>
        <li><a href="patient-appointments.php">Appointments</a></li>
        <li><a href="patient-reports.php">Reports</a></li>
        <li><a href="patient-profile.php" class="active">Profile</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h1>My Profile</h1>
      </header>

      <?php if (isset($message)): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Profile Info -->
      <section class="profile-info">
        <h2>Edit Personal Information</h2>
        <form method="POST">
          <div class="form-grid">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($patient['full_name']); ?>" required />
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($patient['email']); ?>" required />
            </div>

            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($patient['phone']); ?>" required />
            </div>

            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?= htmlspecialchars($patient['address']); ?>" />
            </div>

            
          </div>
          <button type="submit" name="updateProfile">Save Changes</button>
        </form>
      </section>

      <!-- Change Password -->
      <section class="change-password">
        <h2>Change Password</h2>
        <form method="POST">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input type="password" id="currentPassword" name="currentPassword" required />
          </div>

          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input type="password" id="newPassword" name="newPassword" required />
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirm New Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required />
          </div>

          <button type="submit" name="changePassword">Update Password</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

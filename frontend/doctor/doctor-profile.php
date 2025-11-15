<?php
// ✅ Enable errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';

// ✅ Require doctor login
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
  header("Location: ../login.html");
  exit;
}

$doctor_id = $_SESSION['user_id'];

// ✅ Fetch doctor info
$query = "SELECT full_name, email, phone, department FROM doctors WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
  die("Doctor profile not found!");
}

// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $phone = $_POST['phone'];
  $department = $_POST['department'];

  $update_query = "UPDATE doctors SET full_name = ?, email = ?, phone = ?, department = ? WHERE id = ?";
  $stmt = $conn->prepare($update_query);
  $stmt->bind_param("ssssi", $name, $email, $phone, $department, $doctor_id);

  if ($stmt->execute()) {
    echo "<script>alert('Profile updated successfully!'); window.location.href='doctor-profile.php';</script>";
    exit;
  } else {
    echo "<script>alert('Error updating profile.');</script>";
  }
}

// ✅ Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $current = $_POST['currentPassword'];
  $new = $_POST['newPassword'];
  $confirm = $_POST['confirmPassword'];

  // Fetch current password hash
  $stmt = $conn->prepare("SELECT password FROM doctors WHERE id = ?");
  $stmt->bind_param("i", $doctor_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $data = $result->fetch_assoc();

  if (!password_verify($current, $data['password'])) {
    echo "<script>alert('Current password is incorrect!');</script>";
  } elseif ($new !== $confirm) {
    echo "<script>alert('New passwords do not match!');</script>";
  } else {
    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE doctors SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $doctor_id);
    $stmt->execute();
    echo "<script>alert('Password changed successfully!'); window.location.href='doctor-profile.php';</script>";
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doctor Profile - HMS</title>
  <link rel="stylesheet" href="css/doctor-profile.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Doctor Panel</h2>
      <ul>
        <li><a href="doctor-dashboard.php">Dashboard</a></li>
        <li><a href="doctor-patients.php">My Patients</a></li>
        <li><a href="doctor-appointments.php">My Appointments</a></li>
        <li><a href="doctor-profile.php" class="active">Profile</a></li>
        <li><a href="doctor-reports.php">Reports</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h1>My Profile</h1>
      </header>

      <!-- Profile Overview -->
      <section class="profile-overview">
        <div class="profile-header">
       
          <div>
            <h2 id="doctorName"><?= htmlspecialchars($doctor['full_name']); ?></h2>
            <p><strong>Department:</strong> <?= htmlspecialchars($doctor['department']); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($doctor['email']); ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($doctor['phone']); ?></p>
          </div>
        </div>
      </section>

      <!-- Editable Profile Info -->
      <section class="profile-info">
        <h2>Edit Profile Information</h2>
        <form method="POST" id="profileForm">
          <input type="hidden" name="update_profile" value="1">
          <div class="form-grid">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($doctor['full_name']); ?>" required />
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($doctor['email']); ?>" required />
            </div>

            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($doctor['phone']); ?>" required />
            </div>

            <div class="form-group">
              <label for="department">Department</label>
              <input type="text" id="department" name="department" value="<?= htmlspecialchars($doctor['department']); ?>" required />
            </div>

        
          </div>

          <button type="submit">Save Changes</button>
        </form>
      </section>

      <!-- Change Password -->
      <section class="change-password">
        <h2>Change Password</h2>
        <form method="POST" id="passwordForm">
          <input type="hidden" name="change_password" value="1">
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

          <button type="submit">Update Password</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

<?php
include '../../backend/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: /../frontend/login.html");
  exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No patient selected.");
}

// Load patient data first
$stmt = $conn->prepare("SELECT * FROM patients WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

if (!$patient) {
    die("Patient not found.");
}

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['patientName'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $bloodGroup = $_POST['bloodGroup'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $doctor = $_POST['doctor'];
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // If any password field is filled, verify and update password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        // Check current password
        if (!password_verify($current_password, $patient['password'])) {
            echo "<script>alert('Current password is incorrect!'); window.history.back();</script>";
            exit;
        }

        // Check if new passwords match
        if ($new_password !== $confirm_password) {
            echo "<script>alert('New passwords do not match!'); window.history.back();</script>";
            exit;
        }

        // Hash the new password
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        // Update patient info + password
        $stmt = $conn->prepare("UPDATE patients 
            SET full_name=?, age=?, gender=?, blood_group=?, phone=?, address=?, doctor=?, password=? 
            WHERE id=?");
        $stmt->bind_param('sissssssi', $fullName, $age, $gender, $bloodGroup, $phone, $address, $doctor, $hashedPassword, $id);
    } else {
        // Update patient info only (no password change)
        $stmt = $conn->prepare("UPDATE patients 
            SET full_name=?, age=?, gender=?, blood_group=?, phone=?, address=?, doctor=? 
            WHERE id=?");
        $stmt->bind_param('sisssssi', $fullName, $age, $gender, $bloodGroup, $phone, $address, $doctor, $id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Patient updated successfully!'); window.location='patients.php';</script>";
    } else {
        echo "<p>Error updating patient: " . $stmt->error . "</p>";
    }
}

// Fetch doctors for dropdown
$doctors = [];
$result = $conn->query("SELECT id, full_name, department FROM doctors WHERE status='On Call'");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
  }
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Patient - HMS</title>
  <link rel="stylesheet" href="css/patient-details.css">
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="patients.php" class="active">Patients</a></li>
        <li><a href="doctor.php">Doctors</a></li>
        <li><a href="appointments.php">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
        <li><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="content">
      <header class="topbar"><h2>Edit Patient Details</h2></header>

      <section class="add-patient">
        <form method="POST">
          <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="patientName" value="<?= htmlspecialchars($patient['full_name']) ?>" required>
          </div>

          <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" value="<?= htmlspecialchars($patient['age']) ?>" required>
          </div>

          <div class="form-group">
            <label>Gender:</label>
            <select name="gender" required>
              <option <?= $patient['gender']=='Male'?'selected':'' ?>>Male</option>
              <option <?= $patient['gender']=='Female'?'selected':'' ?>>Female</option>
              <option <?= $patient['gender']=='Other'?'selected':'' ?>>Other</option>
            </select>
          </div>

          <div class="form-group">
            <label>Blood Group:</label>
            <input type="text" name="bloodGroup" value="<?= htmlspecialchars($patient['blood_group']) ?>" required>
          </div>

          <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>" required>
          </div>

          <div class="form-group">
            <label>Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($patient['address']) ?>" required>
          </div>

          <div class="form-group">
            <label for="doctor">Assigned Doctor:</label>
            <select id="doctor" name="doctor" required>
              <option value="">Select Doctor</option>
              <?php foreach ($doctors as $doc): ?>
                <option value="<?= htmlspecialchars($doc['full_name']) ?>">
                  <?= htmlspecialchars($doc['full_name'] . " — " . $doc['department']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <br>
          <h3>Change Password (optional)</h3>
          <br>
          <div class="form-group">
            <label>Current Password:</label>
            <input type="password" name="current_password" placeholder="Enter current password">
          </div>

          <div class="form-group">
            <label>New Password:</label>
            <input type="password" name="new_password" placeholder="Enter new password">
          </div>

          <div class="form-group">
            <label>Confirm New Password:</label>
            <input type="password" name="confirm_password" placeholder="Confirm new password">
          </div>

          <button type="submit" class="submit-btn">Update Patient</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

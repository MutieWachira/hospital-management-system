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
    die("No doctor selected.");
}

// ✅ Load doctor data
$stmt = $conn->prepare("SELECT * FROM doctors WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    die("Doctor not found.");
}

// ✅ When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['doctorName'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $department = $_POST['department'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // ✅ If user wants to change password
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        // Check all password fields
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo "<script>alert('Please fill all password fields.'); window.history.back();</script>";
            exit;
        }

        // Verify current password
        if (!password_verify($currentPassword, $doctor['password'])) {
            echo "<script>alert('Current password is incorrect.'); window.history.back();</script>";
            exit;
        }

        // Check password match
        if ($newPassword !== $confirmPassword) {
            echo "<script>alert('New passwords do not match.'); window.history.back();</script>";
            exit;
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update doctor with password change
        $stmt = $conn->prepare("UPDATE doctors 
            SET full_name=?, email=?, age=?, gender=?, department=?, phone=?, address=?, status=?, password=?
            WHERE id=?");
        $stmt->bind_param('ssissssssi', $fullName, $email, $age, $gender, $department, $phone, $address, $status, $hashedPassword, $id);
    } else {
        // Update doctor without password change
        $stmt = $conn->prepare("UPDATE doctors 
            SET full_name=?, email=?, age=?, gender=?, department=?, phone=?, address=?, status=?
            WHERE id=?");
        $stmt->bind_param('ssisssssi', $fullName, $email, $age, $gender, $department, $phone, $address, $status, $id);
    }

    // Execute update
    if ($stmt->execute()) {
        echo "<script>alert('Doctor updated successfully!'); window.location='doctor.php';</script>";
    } else {
        echo "<p>Error updating doctor: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Doctor - HMS</title>
  <link rel="stylesheet" href="css/edit-doc.css">
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="doctor.php" class="active">Doctors</a></li>
        <li><a href="appointments.php">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
        <li><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <main class="content">
      <header class="topbar"><h2>Edit Doctor Details</h2></header>

      <section class="add-doctor">
        <form method="POST">
          <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="doctorName" value="<?= htmlspecialchars($doctor['full_name']) ?>" required>
          </div>

          <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" required>
          </div>

          <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" value="<?= htmlspecialchars($doctor['age']) ?>" required>
          </div>

          <div class="form-group">
            <label>Gender:</label>
            <select name="gender" required>
              <option <?= $doctor['gender']=='Male'?'selected':'' ?>>Male</option>
              <option <?= $doctor['gender']=='Female'?'selected':'' ?>>Female</option>
              <option <?= $doctor['gender']=='Other'?'selected':'' ?>>Other</option>
            </select>
          </div>

          <div class="form-group">
            <label>Department:</label>
            <input type="text" name="department" value="<?= htmlspecialchars($doctor['department']) ?>" required>
          </div>

          <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($doctor['phone']) ?>" required>
          </div>

          <div class="form-group">
            <label>Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($doctor['address']) ?>" required>
          </div>

          <div class="form-group">
            <label>Status:</label>
            <select name="status" required>
              <option <?= $doctor['status']=='OnCall'?'selected':'' ?>>On Call</option>
              <option <?= $doctor['status']=='OffCall'?'selected':'' ?>>Off Call</option>
              <option <?= $doctor['status']=='Leave'?'selected':'' ?>>Leave</option>
            </select>
          </div>

          <!-- ✅ Password Update Section -->
          <h3 style="margin-top:20px;">Change Password (Optional)</h3>
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

          <button type="submit" class="submit-btn">Update Doctor</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

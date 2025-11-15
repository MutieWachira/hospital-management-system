<?php
include '../../backend/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
  exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No appointment selected.");
}

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_name = $_POST['patient-name'];
    $doctor_name = $_POST['doctor-name'];
    $patient_email = $_POST['email'];
    $description = $_POST['description'];
    $appointment_date = $_POST['appointment-date'];
    $appointment_time = $_POST['appointment-time'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE appointments 
        SET patient_name=?, patient_email=?, doctor_name=?, description=?, appointment_date=?, appointment_time=?, status=?
        WHERE id=?");
    $stmt->bind_param('sssssssi', $patient_name, $patient_email, $doctor_name, $description, $appointment_date, $appointment_time, $status, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Appointment updated successfully!'); window.location='appointments.php';</script>";
    } else {
        echo "<p>Error updating appointment: " . $stmt->error . "</p>";
    }
}

// Load appointment data
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Appointment - HMS</title>
  <link rel="stylesheet" href="css/edit-app.css">
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
      <li><a href="index.php">Dashboard</a></li>
      <li><a href="patients.php" >Patients</a></li>
      <li><a href="doctor.php" class="active">Doctors</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="settings.php">Profile</a></li>
      <li><a href="../../backend/logout.php">Logout</a></li>
    </ul>
    </aside>

    <main class="content">
      <header class="topbar"><h2>Edit Appointment Details</h2></header>

      <section class="add-appointment">
        <form method="POST">
          <div class="form-group">
            <label>Patient Name:</label>
            <input type="text" name="patient-name" value="<?= htmlspecialchars($appointment['patient_name']) ?>" required>
          </div>

          <div class="form-group">
            <label>Doctor Name:</label>
            <input type="text" name="doctor-name" value="<?= htmlspecialchars($appointment['doctor_name']) ?>" required>
          </div>

           <div class="form-group">
            <label>Patient Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($appointment['patient_email']) ?>" required>
          </div>

          <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="3" placeholder="Enter description" required><?= htmlspecialchars($appointment['description']) ?></textarea>
          </div>

           <div class="form-group">
            <label>Appointment Date:</label>
            <input type="date" name="appointment-date" value="<?= htmlspecialchars($appointment['appointment_date']) ?>" required>
          </div>

          <div class="form-group">
            <label>Appointment Time:</label>
            <input type="time" name="appointment-time" value="<?= htmlspecialchars($appointment['appointment_time']) ?>" required>
          </div>

          <div class="form-group">
            <label>Status:</label>
            <select name="status" required>
              <option <?= $appointment['status']=='accepted'?'selected':'' ?>>Accepted</option>
              <option <?= $appointment['status']=='rejected'?'selected':'' ?>>Rejected</option>
              <option <?= $appointment['status']=='pending'?'selected':'' ?>>Pending</option>
              <option <?= $appointment['status']=='completed'?'selected':'' ?>>Completed</option>
            </select>
          </div>

          <button type="submit" class="submit-btn">Update Appointment</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

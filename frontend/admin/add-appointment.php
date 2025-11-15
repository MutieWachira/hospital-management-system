<?php
include '../../backend/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);


session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
   header("Location: ../login.html");
  exit;
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Appointment - HMS</title>
  <link rel="stylesheet" href="css/add-appointment.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="doctor.php">Doctors</a></li>
        <li><a href="appointments.php" class="active">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
        <li><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="content">
      <header class="topbar">
        <h2>Add an Appointment</h2>
      </header>

      <section class="add-appointment">
        <h2>Appointment Booking Form</h2>
        <form method="POST" action="../../backend/add_appointment.php" id="addAppointmentForm">
          
          <div class="form-group">
            <label for="patientName">Full Name:</label>
            <input type="text" id="patientName" name="full_name" placeholder="Enter full name" required />
          </div>

          <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter email" required />
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

          <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="3" placeholder="Enter description" required></textarea>
          </div>

          <div class="form-group">
            <label for="appointment-date">Appointment Date:</label>
            <input type="date" id="appointment-date" name="appointment_date" required />
          </div>

          <div class="form-group">
            <label for="appointment-time">Appointment Time:</label>
            <input type="time" id="appointment-time" name="appointment_time" required />
          </div>
          
          <div class="form-group">
            <label for="status">Status:</label>
            <select id="status" name="status" required>
              <option value="">Select Status</option>
              <option value="Pending">Pending</option>
              <option value="Accepted">Accepted</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>

          <button type="submit" class="submit-btn" name="submit">Save Appointment</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

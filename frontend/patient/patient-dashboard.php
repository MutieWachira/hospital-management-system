<?php
// Show errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to DB
include '../../backend/db_connect.php';

// Start session and check if patient is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header('Location: ../../frontend/login.html');
  exit;
}

$patient_id = (int) $_SESSION['user_id'];

// Fetch patient info
$stmt = $conn->prepare("SELECT full_name, email, phone FROM patients WHERE id = ?");
if (!$stmt) {
  error_log("Prepare failed (patient_query): " . $conn->error);
  header('Location: ../../frontend/login.html');
  exit;
}
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient_result = $stmt->get_result();
$patient = $patient_result ? $patient_result->fetch_assoc() : null;
$stmt->close();

if (!$patient) {
  // no such patient -> force logout / redirect
  header('Location: ../../frontend/login.html');
  exit;
}

// Fetch UPCOMING appointments (today or later)
$appointments = false;
$today = date('Y-m-d');
$appointments_query = "
  SELECT id, doctor_name, appointment_date, appointment_time, status
  FROM appointments
  WHERE patient_email = ? 
  AND appointment_date >= ?
  ORDER BY appointment_date ASC, appointment_time ASC
  LIMIT 5
";
$stmt = $conn->prepare($appointments_query);
if ($stmt) {
  $stmt->bind_param("ss", $patient['email'], $today);
  $stmt->execute();
  $appointments = $stmt->get_result();
  $stmt->close();
} else {
  error_log("Prepare failed (appointments_query): " . $conn->error);
}

// Fetch latest prescriptions / reports
$reports = false;
$reports_query = "
  SELECT r.id, d.full_name AS doctor_name, r.diagnosis, r.treatment, r.report_date
  FROM doctor_reports r
  LEFT JOIN doctors d ON r.doctor_id = d.id
  WHERE r.patient_id = ?
  ORDER BY r.report_date DESC
  LIMIT 5
";
$stmt = $conn->prepare($reports_query);
if ($stmt) {
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $reports = $stmt->get_result();
  $stmt->close();
} else {
  error_log("Prepare failed (reports_query): " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Dashboard - HMS</title>
  <link rel="stylesheet" href="css/patient-dashboard.css">
  <style>
    .status.pending { color: orange; font-weight: bold; }
    .status.approved, .status.completed { color: green; font-weight: bold; }
    .status.cancelled, .status.declined { color: red; font-weight: bold; }
    .no-data { text-align: center; color: #777; font-style: italic; }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Patient Panel</h2>
      <ul>
        <li><a href="patient-dashboard.php" class="active">Dashboard</a></li>
        <li><a href="patient-appointments.php">My Appointments</a></li>
        <li><a href="patient-reports.php">My Reports</a></li>
        <li><a href="patient-profile.php">Profile</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h1>Welcome, <?= htmlspecialchars($patient['full_name']); ?> 👋</h1>
      </header>

      <!-- Overview Cards -->
      <section class="overview">
        <div class="card">
          <h3>Upcoming Appointments</h3>
          <p><?= $appointments ? $appointments->num_rows : 0; ?></p>
        </div>
        <div class="card">
          <h3>Recent Reports</h3>
          <p><?= $reports ? $reports->num_rows : 0; ?></p>
        </div>
        <div class="card">
          <h3>Contact Info</h3>
          <p><?= htmlspecialchars($patient['email']); ?><br><?= htmlspecialchars($patient['phone']); ?></p>
        </div>
      </section>

      <!-- Appointments Section -->
      <section class="appointments">
        <h2>Upcoming Appointments</h2>
        <table>
          <thead>
            <tr>
              <th>Doctor</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($appointments && $appointments->num_rows > 0): ?>
              <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($a['doctor_name']); ?></td>
                  <td><?= htmlspecialchars($a['appointment_date']); ?></td>
                  <td><?= htmlspecialchars($a['appointment_time']); ?></td>
                  <td><span class="status <?= strtolower($a['status']); ?>"><?= htmlspecialchars($a['status']); ?></span></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" class="no-data">No upcoming appointments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <!-- Recent Reports Section -->
      <section class="reports">
        <h2>Recent Medical Reports</h2>
        <table>
          <thead>
            <tr>
              <th>Doctor</th>
              <th>Diagnosis</th>
              <th>Treatment</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($reports && $reports->num_rows > 0): ?>
              <?php while ($r = $reports->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($r['doctor_name']); ?></td>
                  <td><?= htmlspecialchars($r['diagnosis']); ?></td>
                  <td><?= htmlspecialchars($r['treatment']); ?></td>
                  <td><?= htmlspecialchars($r['report_date']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" class="no-data">No reports available.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>

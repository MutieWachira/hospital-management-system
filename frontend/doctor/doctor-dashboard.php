<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../../backend/db_connect.php';
session_start();

// require doctor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
  header("Location: ../../frontend/login.html");
  exit;
}

// Fetch quick stats
$doctor_id = (int) $_SESSION['user_id'];
$doctor_name = $_SESSION['name'] ?? '';

// Total patients added by this doctor
$patientsCount = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM patients WHERE id = ?")) {
  $stmt->bind_param("i", $doctor_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $patientsCount = $res->fetch_assoc()['total'] ?? 0;
  $stmt->close();
} else if ($doctor_name && $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM patients WHERE doctor = ?")) {
  // fallback if patients table stores doctor name instead of id
  $stmt->bind_param("s", $doctor_name);
  $stmt->execute();
  $res = $stmt->get_result();
  $patientsCount = $res->fetch_assoc()['total'] ?? 0;
  $stmt->close();
}

// Today's appointments
$today = date('Y-m-d');
$appointmentsCount = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?")) {
  $stmt->bind_param("is", $doctor_id, $today);
  $stmt->execute();
  $res = $stmt->get_result();
  $appointmentsCount = $res->fetch_assoc()['total'] ?? 0;
  $stmt->close();
}

// Total reports (if reports table exists)
// $reportsCount = 0;
// if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM reports WHERE doctor_id = ?")) {
//   $stmt->bind_param("i", $doctor_id);
//   $stmt->execute();
//   $res = $stmt->get_result();
//   $reportsCount = $res->fetch_assoc()['total'] ?? 0;
//   $stmt->close();
//}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Dashboard - HMS</title>
  <link rel="stylesheet" href="css/doctor-dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="container">

    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Doctor Panel</h2>
      <ul>
        <li><a href="doctor-dashboard.php" class="active">Dashboard</a></li>
        <li><a href="doctor-patients.php">My Patients</a></li>
        <li><a href="doctor-appointments.php">Appointments</a></li>
        <li><a href="doctor-reports.php">Reports</a></li>
        <li><a href="doctor-profile.php">Profile</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
      </header>

      <!-- Quick Stats -->
      <section class="stats">
        <div class="stat-card">
          <h3><?= $patientsCount ?></h3>
          <p>Total Patients</p>
        </div>
        <div class="stat-card">
          <h3><?= $appointmentsCount ?></h3>
          <p>Today's Appointments</p>
        </div>
        <!-- <div class="stat-card">
          <h3><?= $reportsCount ?></h3>
          <p>Total Reports</p>
        </div> -->
      </section>

      <!-- Chart Section -->
      <section class="chart-section">
        <h3>Monthly Patients Overview</h3>
        <canvas id="patientChart"></canvas>
      </section>
    </main>
  </div>

  <script>
    // Simple bar chart for patients added each month
    const ctx = document.getElementById('patientChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Patients Added',
          data: [5, 3, 7, 4, 6, 8, 2, 9, 10, 6, 4, 5], // You can later make this dynamic
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: '#007bff',
          borderWidth: 1,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
</body>
</html>

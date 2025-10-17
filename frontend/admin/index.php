<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../../backend/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: /../frontend/login.html");
  exit();
}

// ===== Fetch Counts =====
$totalPatients = $conn->query("SELECT COUNT(*) AS total FROM patients")->fetch_assoc()['total'] ?? 0;
$totalDoctors = $conn->query("SELECT COUNT(*) AS total FROM doctors")->fetch_assoc()['total'] ?? 0;
$totalAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()['total'] ?? 0;
$completedAppointments = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status='Completed'")->fetch_assoc()['total'] ?? 0;

// ===== Fetch Doctor Workload (Appointments per Doctor) =====
$doctorWorkload = [];
$result = $conn->query("
  SELECT d.full_name AS doctor_name, COUNT(a.id) AS total_appointments
  FROM doctors d
  LEFT JOIN appointments a ON d.id = a.doctor_id
  GROUP BY d.id
");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $doctorWorkload[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - HMS</title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- ===== Sidebar ===== -->
  <aside class="sidebar">
    <h2>HMS Admin</h2>
    <ul class="menu">
      <li><a href="index.php" class="active">Dashboard</a></li>
      <li><a href="patients.php">Patients</a></li>
      <li><a href="doctor.php">Doctors</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="settings.php">Profile</a></li>
    </ul>

    <div class="logout-section">
      <a href="../../backend/logout.php" class="logout-btn">Logout</a>
    </div>
  </aside>

  <!-- ===== Main Section ===== -->
  <main class="main">
    <div class="topbar">
      <h2>Admin Dashboard</h2>
    </div>

    <!-- ===== Summary Cards ===== -->
    <section class="summary-cards">
      <div class="card patients">
        <h3>Total Patients</h3>
        <p><?= $totalPatients ?></p>
      </div>
      <div class="card doctors">
        <h3>Total Doctors</h3>
        <p><?= $totalDoctors ?></p>
      </div>
      <div class="card appointments">
        <h3>Total Appointments</h3>
        <p><?= $totalAppointments ?></p>
      </div>
      <div class="card completed">
        <h3>Completed</h3>
        <p><?= $completedAppointments ?></p>
      </div>
    </section>

    <!-- ===== Doctor Workload Chart ===== -->
    <section class="chart-section">
      <h3>Doctor Workload Overview</h3>
      <canvas id="doctorWorkloadChart"></canvas>
    </section>
  </main>

  <script>
    // Prepare doctor workload data
    const doctorNames = <?= json_encode(array_column($doctorWorkload, 'doctor_name')) ?>;
    const doctorAppointments = <?= json_encode(array_column($doctorWorkload, 'total_appointments')) ?>;

    const ctx = document.getElementById('doctorWorkloadChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: doctorNames,
        datasets: [{
          label: 'Appointments per Doctor',
          data: doctorAppointments,
          backgroundColor: '#1b88ee'
        }]
      },
      options: {
        responsive: true,
        plugins: { 
          legend: { display: false },
          title: {
            display: true,
            text: 'Number of Appointments per Doctor'
          }
        },
        scales: { 
          y: { beginAtZero: true },
          x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } }
        }
      }
    });
  </script>
</body>
</html>

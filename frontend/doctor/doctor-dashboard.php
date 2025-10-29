<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../../backend/db_connect.php';
session_start();

// Ensure doctor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
  header("Location: ../../frontend/login.html");
  exit;
}

$doctor_id = (int) $_SESSION['user_id'];
$doctor_name = $_SESSION['name'] ?? '';

// =============================
// Quick Stats
// =============================

// 1️⃣ Total patients seen (appointments completed)
$query = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE doctor_name = ? AND status = 'Completed'");
$query->bind_param("s", $doctor_name);
$query->execute();
$patientsCount = $query->get_result()->fetch_assoc()['total'] ?? 0;
$query->close();

// 2️⃣ Today's appointments (any status)
$today = date('Y-m-d');
$query = $conn->prepare("SELECT COUNT(*) AS total FROM appointments WHERE doctor_name = ? AND appointment_date = ?");
$query->bind_param("ss", $doctor_name, $today);
$query->execute();
$appointmentsCount = $query->get_result()->fetch_assoc()['total'] ?? 0;
$query->close();

// 3️⃣ Reports created by this doctor
$query = $conn->prepare("SELECT COUNT(*) AS total FROM doctor_reports WHERE doctor_id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$reportsCount = $query->get_result()->fetch_assoc()['total'] ?? 0;
$query->close();

// =============================
// Chart Data: Patients Seen Each Month
// =============================
$chartData = array_fill(1, 12, 0); // Initialize months Jan–Dec

$sql = "
  SELECT MONTH(appointment_date) AS month, COUNT(*) AS total
  FROM appointments
  WHERE doctor_name = ? AND status = 'Completed'
  GROUP BY MONTH(appointment_date)
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $doctor_name);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
  $chartData[(int)$row['month']] = (int)$row['total'];
}
$stmt->close();
$conn->close();

// Encode for Chart.js
$chartDataJson = json_encode(array_values($chartData));
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
          <p>Patients Seen</p>
        </div>
        <div class="stat-card">
          <h3><?= $appointmentsCount ?></h3>
          <p>Today's Appointments</p>
        </div>
        <div class="stat-card">
          <h3><?= $reportsCount ?></h3>
          <p>Total Reports</p>
        </div> 
      </section>

      <!-- Chart Section -->
      <section class="chart-section">
        <h3>Monthly Patients Seen Overview</h3>
        <canvas id="patientChart"></canvas>
      </section>
    </main>
  </div>

  <script>
    const monthlyData = <?= $chartDataJson ?>;

    const ctx = document.getElementById('patientChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Patients Seen',
          data: monthlyData,
          fill: true,
          borderColor: '#1b88ee',
          backgroundColor: 'rgba(27,136,238,0.1)',
          tension: 0.3,
          pointBackgroundColor: '#1b88ee'
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: {
          legend: { display: true, position: 'top' },
          title: {
            display: true,
            text: 'Number of Patients Seen per Month'
          }
        }
      }
    });
  </script>
</body>
</html>

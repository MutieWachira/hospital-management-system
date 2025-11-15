<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
session_start();

// require admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
  exit;
}

// Initialize counters
$totalPatients = $totalDoctors = $totalAppointments = $completedAppointments = 0;
$recentAppointments = [];
$monthlyAppointments = array_fill(1, 12, 0); // keys 1..12

// Fetch total patients
$res = $conn->query("SELECT COUNT(*) AS total FROM patients");
if ($res) {
  $row = $res->fetch_assoc();
  $totalPatients = (int)($row['total'] ?? 0);
} else {
  error_log("Patients count error: " . $conn->error);
}

// Fetch total doctors
$res = $conn->query("SELECT COUNT(*) AS total FROM doctors");
if ($res) {
  $row = $res->fetch_assoc();
  $totalDoctors = (int)($row['total'] ?? 0);
} else {
  error_log("Doctors count error: " . $conn->error);
}

// Fetch total appointments
$res = $conn->query("SELECT COUNT(*) AS total FROM appointments");
if ($res) {
  $row = $res->fetch_assoc();
  $totalAppointments = (int)($row['total'] ?? 0);
} else {
  error_log("Appointments count error: " . $conn->error);
}

// Fetch completed appointments (case-insensitive)
$res = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE LOWER(status) = 'completed'");
if ($res) {
  $row = $res->fetch_assoc();
  $completedAppointments = (int)($row['total'] ?? 0);
} else {
  error_log("Completed count error: " . $conn->error);
}

// Fetch monthly appointment data for current year
$res = $conn->query("
  SELECT MONTH(appointment_date) AS month, COUNT(*) AS total
  FROM appointments
  WHERE YEAR(appointment_date) = YEAR(CURDATE())
  GROUP BY MONTH(appointment_date)
");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $m = (int)$row['month'];
    if ($m >= 1 && $m <= 12) {
      $monthlyAppointments[$m] = (int)$row['total'];
    }
  }
} else {
  error_log("Monthly query error: " . $conn->error);
}

// Build ordered array for JS (Jan..Dec)
$monthlyOrdered = [];
for ($m = 1; $m <= 12; $m++) {
  $monthlyOrdered[] = $monthlyAppointments[$m] ?? 0;
}

// Fetch recent 10 appointments (use patient_name/doctor_name columns from your schema)
$res = $conn->query("
  SELECT appointment_date,
         appointment_time,
         patient_name,
         doctor_name,
         status
  FROM appointments
  ORDER BY appointment_date DESC, appointment_time DESC
  LIMIT 10
");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $recentAppointments[] = $row;
  }
} else {
  error_log("Recent appointments query error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports - Admin Dashboard</title>
  <link rel="stylesheet" href="css/reports.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- ===== Sidebar ===== -->
  <aside class="sidebar">
    <h2>HMS Admin</h2>
    <ul>
      <li><a href="index.php">Dashboard</a></li>
      <li><a href="patients.php">Patients</a></li>
      <li><a href="doctor.php">Doctors</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="reports.php" class="active">Reports</a></li>
      <li><a href="settings.php">Profile</a></li>
      <li><a href="../../backend/logout.php">Logout</a></li>
    </ul>
  </aside>

  <!-- ===== Main Section ===== -->
  <main class="main">
    <div class="topbar">
      <h2>System Reports</h2>
      <div class="topbar-right"></div>
    </div>

    <!-- ===== Summary Cards ===== -->
    <section class="summary-cards">
      <div class="card patients">
        <h3>Total Patients</h3>
        <p id="totalPatients"><?= (int)$totalPatients ?></p>
      </div>
      <div class="card doctors">
        <h3>Total Doctors</h3>
        <p id="totalDoctors"><?= (int)$totalDoctors ?></p>
      </div>
      <div class="card appointments">
        <h3>Total Appointments</h3>
        <p id="totalAppointments"><?= (int)$totalAppointments ?></p>
      </div>
      <div class="card completed">
        <h3>Completed</h3>
        <p id="completedAppointments"><?= (int)$completedAppointments ?></p>
      </div>
    </section>

    <!-- ===== Appointment Chart ===== -->
    <section class="chart-section">
      <h3>Monthly Appointments Overview (<?= date('Y') ?>)</h3>
      <canvas id="appointmentsChart"></canvas>
    </section>

    <!-- ===== Table Report ===== -->
    <section class="table-section">
      <h3>Recent Appointment Summary</h3>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($recentAppointments)): ?>
            <?php foreach ($recentAppointments as $appointment): ?>
              <tr>
                <td><?= htmlspecialchars($appointment['appointment_date'] ?? '') ?></td>
                <td><?= htmlspecialchars($appointment['appointment_time'] ?? '') ?></td>
                <td><?= htmlspecialchars($appointment['patient_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($appointment['doctor_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($appointment['status'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5">No recent appointments found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <script>
    const monthlyAppointments = <?= json_encode($monthlyOrdered) ?>;
    const ctx = document.getElementById('appointmentsChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{ label: 'Appointments', data: monthlyAppointments, backgroundColor: '#1b88ee' }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  </script>
</body>
</html>

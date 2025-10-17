<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include your database connection
include '../../backend/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: /../frontend/login.html");
  exit;
}

// Initialize counters
$totalPatients = $totalDoctors = $totalAppointments = $completedAppointments = 0;
$recentAppointments = [];
$monthlyAppointments = array_fill(1, 12, 0); // Default 12 months

// Fetch total patients
$result = $conn->query("SELECT COUNT(*) AS total FROM patients");
if ($result && $row = $result->fetch_assoc()) {
  $totalPatients = $row['total'];
}

// Fetch total doctors
$result = $conn->query("SELECT COUNT(*) AS total FROM doctors");
if ($result && $row = $result->fetch_assoc()) {
  $totalDoctors = $row['total'];
}

// Fetch total appointments
$result = $conn->query("SELECT COUNT(*) AS total FROM appointments");
if ($result && $row = $result->fetch_assoc()) {
  $totalAppointments = $row['total'];
}

// Fetch completed appointments
$result = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE status='Completed'");
if ($result && $row = $result->fetch_assoc()) {
  $completedAppointments = $row['total'];
}

// Fetch monthly appointment data
$result = $conn->query("
  SELECT MONTH(appointment_date) AS month, COUNT(*) AS total
  FROM appointments
  WHERE YEAR(appointment_date) = YEAR(CURDATE())
  GROUP BY MONTH(appointment_date)
");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $monthlyAppointments[(int)$row['month']] = (int)$row['total'];
  }
}

// Fetch recent 10 appointments
$result = $conn->query("
  SELECT a.appointment_date, p.full_name AS patient, d.full_name AS doctor, a.status
  FROM appointments a
  LEFT JOIN patients p ON a.id = p.id
  LEFT JOIN doctors d ON a.id = d.id
  ORDER BY a.appointment_date DESC
  LIMIT 10
");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $recentAppointments[] = $row;
  }
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
      <div class="topbar-right">
        
      </div>
    </div>

    <!-- ===== Summary Cards ===== -->
    <section class="summary-cards">
      <div class="card patients">
        <h3>Total Patients</h3>
        <p id="totalPatients"><?= $totalPatients ?></p>
      </div>
      <div class="card doctors">
        <h3>Total Doctors</h3>
        <p id="totalDoctors"><?= $totalDoctors ?></p>
      </div>
      <div class="card appointments">
        <h3>Total Appointments</h3>
        <p id="totalAppointments"><?= $totalAppointments ?></p>
      </div>
      <div class="card completed">
        <h3>Completed</h3>
        <p id="completedAppointments"><?= $completedAppointments ?></p>
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
            <th>Patient</th>
            <th>Doctor</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($recentAppointments)): ?>
            <?php foreach ($recentAppointments as $appointment): ?>
              <tr>
                <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                <td><?= htmlspecialchars($appointment['patient']) ?></td>
                <td><?= htmlspecialchars($appointment['doctor']) ?></td>
                <td><?= htmlspecialchars($appointment['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4">No recent appointments found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <script>
    // Convert PHP monthly data to JS array
    const monthlyAppointments = <?= json_encode(array_values($monthlyAppointments)) ?>;
    const ctx = document.getElementById('appointmentsChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Appointments',
          data: monthlyAppointments,
          backgroundColor: '#1b88ee'
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
</body>
</html>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../../backend/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  // use a relative redirect from frontend/admin to frontend/login.html
  header("Location: ../login.html");
  exit();
}

// ===== Fetch Counts =====
function fetch_count($conn, $sql) {
  $res = $conn->query($sql);
  if (! $res) return 0;
  $row = $res->fetch_assoc();
  return (int) ($row['total'] ?? 0);
}

$totalPatients = fetch_count($conn, "SELECT COUNT(*) AS total FROM patients");
$totalDoctors = fetch_count($conn, "SELECT COUNT(*) AS total FROM doctors");
$totalAppointments = fetch_count($conn, "SELECT COUNT(*) AS total FROM appointments");
$completedAppointments = fetch_count($conn, "SELECT COUNT(*) AS total FROM appointments WHERE LOWER(status) = 'completed'");

// ===== Fetch Doctor Workload (Appointments per Doctor) =====
$doctorWorkload = [];

// detect whether appointments table has a doctor_id FK
$hasDoctorId = false;
$colCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'doctor_id'");
if ($colCheck && $colCheck->num_rows > 0) {
  $hasDoctorId = true;
}

if ($hasDoctorId) {
  $result = $conn->query("
    SELECT d.full_name AS doctor_name, COUNT(a.id) AS total_appointments
    FROM doctors d
    LEFT JOIN appointments a ON d.id = a.doctor_id
    GROUP BY d.id
    ORDER BY total_appointments DESC
  ");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $doctorWorkload[] = [
        'doctor_name' => $row['doctor_name'] ?? '—',
        'total_appointments' => (int)($row['total_appointments'] ?? 0)
      ];
    }
  } else {
    error_log("Doctor workload query failed: " . $conn->error);
  }
} else {
  // appointments table only stores doctor_name — count by matching full_name
  $docRes = $conn->query("SELECT full_name FROM doctors");
  if ($docRes) {
    while ($doc = $docRes->fetch_assoc()) {
      $dname = $doc['full_name'];
      $cstmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_name = ?");
      if ($cstmt) {
        $cstmt->bind_param("s", $dname);
        $cstmt->execute();
        $cres = $cstmt->get_result()->fetch_assoc();
        $count = (int)($cres['cnt'] ?? 0);
        $cstmt->close();
      } else {
        $count = 0;
      }
      $doctorWorkload[] = ['doctor_name' => $dname, 'total_appointments' => $count];
    }
  } else {
    // fallback: group by doctor_name from appointments table
    $gres = $conn->query("SELECT doctor_name, COUNT(*) AS total FROM appointments GROUP BY doctor_name ORDER BY total DESC");
    if ($gres) {
      while ($row = $gres->fetch_assoc()) {
        $doctorWorkload[] = [
          'doctor_name' => $row['doctor_name'] ?? '—',
          'total_appointments' => (int)($row['total'] ?? 0)
        ];
      }
    } else {
      error_log("Fallback doctor workload query failed: " . $conn->error);
    }
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

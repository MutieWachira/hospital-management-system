<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';

// Require doctor login
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
  header('Location: ../../frontend/login.html');
  exit;
}

// Fetch reports with patient details
$query = "
  SELECT 
    doctor_reports.id AS report_id,
    patients.full_name AS patient_name,
    doctor_reports.diagnosis AS diagnosis,
    doctor_reports.treatment AS treatment,
    doctor_reports.report_date AS report_date
  FROM doctor_reports
  INNER JOIN patients ON doctor_reports.patient_id = patients.id
  ORDER BY doctor_reports.report_date DESC
";
$result = mysqli_query($conn, $query);

if (!$result) {
  die('Database query failed: ' . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Reports - Doctor Portal</title>
  <link rel="stylesheet" href="css/doctor-reports.css">
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <h2>Doctor Portal</h2>
    <ul>
      <li><a href="doctor-dashboard.php">Dashboard</a></li>
      <li><a href="doctor-patients.php">My Patients</a></li>
      <li><a href="doctor-appointments.php">My Appointments</a></li>
      <li><a href="doctor-reports.php" class="active">Reports</a></li>
      <li><a href="doctor-profile.php">Profile</a></li>
      <li><a href="../../backend/logout.php" class="logout-btn">Logout</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="main">
    <div class="topbar">
      <h2>Patient Reports</h2>
      <div class="actions">
        <input type="text" id="searchInput" placeholder="Search by patient name...">
        <input type="date" id="filterDate">
        <button onclick="filterReports()">Filter</button>
        <button onclick="printReports()">Print</button>
      </div>
    </div>

    <!-- Reports Table -->
    <section class="reports-section">
      <table id="reportsTable">
        <thead>
          <tr>
            <th>Report ID</th>
            <th>Patient Name</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <tr>
                <td><?= htmlspecialchars($row['report_id']); ?></td>
                <td><?= htmlspecialchars($row['patient_name']); ?></td>
                <td><?= htmlspecialchars($row['diagnosis']); ?></td>
                <td><?= htmlspecialchars($row['treatment']); ?></td>
                <td><?= htmlspecialchars($row['report_date']); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5" class="no-data">No reports found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <script>
    // 🔍 Filter by name or date
    function filterReports() {
      const searchValue = document.getElementById("searchInput").value.toLowerCase();
      const filterDate = document.getElementById("filterDate").value;
      const table = document.getElementById("reportsTable");
      const rows = table.getElementsByTagName("tr");

      for (let i = 1; i < rows.length; i++) {
        const name = rows[i].getElementsByTagName("td")[1]?.innerText.toLowerCase() || "";
        const date = rows[i].getElementsByTagName("td")[4]?.innerText || "";
        const matchName = name.includes(searchValue);
        const matchDate = !filterDate || date === filterDate;

        rows[i].style.display = (matchName && matchDate) ? "" : "none";
      }
    }

    //  Print Reports
    function printReports() {
      const printContent = document.querySelector(".reports-section").innerHTML;
      const printWindow = window.open('', '', 'width=900,height=700');
      printWindow.document.write(`
        <html>
          <head>
            <title>Doctor Reports - Print</title>
            <style>
              body { font-family: Arial, sans-serif; padding: 20px; }
              h2 { text-align: center; margin-bottom: 20px; }
              table { width: 100%; border-collapse: collapse; }
              th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
              th { background-color: #2ecc71; color: #fff; }
            </style>
          </head>
          <body>
            <h2>Doctor Reports - Printed Copy</h2>
            ${printContent}
          </body>
        </html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  </script>
</body>
</html>

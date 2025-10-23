<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
session_start();

// ✅ Ensure patient is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header('Location: ../../frontend/login.html');
  exit;
}

$patient_id = $_SESSION['user_id'];

// ✅ Fetch reports from database
$query = "
  SELECT dr.id, d.full_name AS doctor_name, dr.diagnosis, dr.treatment, dr.report_date, dr.notes
  FROM doctor_reports dr
  INNER JOIN doctors d ON dr.doctor_id = d.id
  WHERE dr.patient_id = ?
  ORDER BY dr.report_date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$reports = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Reports - HMS</title>
  <link rel="stylesheet" href="css/patient-reports.css">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Patient Panel</h2>
      <ul>
        <li><a href="patient-dashboard.php">Dashboard</a></li>
        <li><a href="patient-appointments.php">Appointments</a></li>
        <li><a href="patient-reports.php" class="active">My Reports</a></li>
        <li><a href="patient-profile.php">Profile</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Section -->
    <main class="content">
      <header class="topbar">
        <h1>My Medical Reports</h1>
        <div class="actions">
          <input type="text" id="searchInput" placeholder="Search by doctor name...">
          <input type="date" id="filterDate">
          <button onclick="filterReports()">Filter</button>
          <button onclick="printReports()">Print</button>
        </div>
      </header>

      <section class="reports">
        <table id="reportsTable">
          <thead>
            <tr>
              <th>Report ID</th>
              <th>Doctor</th>
              <th>Diagnosis</th>
              <th>Treatment</th>
              <th>Date</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($reports->num_rows > 0): ?>
              <?php while ($row = $reports->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['id']); ?></td>
                  <td><?= htmlspecialchars($row['doctor_name']); ?></td>
                  <td><?= htmlspecialchars($row['diagnosis']); ?></td>
                  <td><?= htmlspecialchars($row['treatment']); ?></td>
                  <td><?= htmlspecialchars($row['report_date']); ?></td>
                  <td><?= htmlspecialchars($row['notes']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="no-data">No reports available yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <script>
    // 🔍 Filter reports by doctor name or date
    function filterReports() {
      const searchValue = document.getElementById("searchInput").value.toLowerCase();
      const filterDate = document.getElementById("filterDate").value;
      const table = document.getElementById("reportsTable");
      const rows = table.getElementsByTagName("tr");

      for (let i = 1; i < rows.length; i++) {
        const doctor = rows[i].getElementsByTagName("td")[1]?.innerText.toLowerCase();
        const date = rows[i].getElementsByTagName("td")[4]?.innerText;
        const matchDoctor = doctor.includes(searchValue);
        const matchDate = !filterDate || date === filterDate;

        rows[i].style.display = (matchDoctor && matchDate) ? "" : "none";
      }
    }

    // 🖨️ Print reports section
    function printReports() {
      const printContent = document.querySelector(".reports").innerHTML;
      const printWindow = window.open('', '', 'width=900,height=700');
      printWindow.document.write('<html><head><title>Print Reports</title>');
      printWindow.document.write('<link rel="stylesheet" href="css/patient-reports.css">');
      printWindow.document.write('</head><body>');
      printWindow.document.write('<h2>Patient Medical Reports</h2>');
      printWindow.document.write(printContent);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.print();
    }
  </script>
</body>
</html>

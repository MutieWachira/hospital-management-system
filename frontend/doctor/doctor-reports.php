<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
require_once '../../backend/email_helper.php';

session_start();

// Require doctor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
  header('Location: ../../frontend/login.html');
  exit;
}

$doctor_id = (int) $_SESSION['user_id'];
$doctor_name = $_SESSION['name'] ?? 'Doctor';

// ==============================
// Fetch reports with patient details
// ==============================
$query = "
  SELECT 
    dr.id AS report_id,
    p.id AS patient_id,
    p.full_name AS patient_name,
    p.email AS patient_email,
    dr.diagnosis AS diagnosis,
    dr.treatment AS treatment,
    dr.report_date AS report_date
  FROM doctor_reports dr
  INNER JOIN patients p ON dr.patient_id = p.id
  WHERE dr.doctor_id = ?
  ORDER BY dr.report_date DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
  die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// ==============================
// Optional: Send notification email when new reports are added
// (Only triggered if a `new_report` GET parameter is present, so it doesn’t spam every page load)
// ==============================
if (isset($_GET['new_report']) && isset($_GET['pid'])) {
  $patient_id = (int) $_GET['pid'];
  $emailQ = $conn->prepare("SELECT email, full_name FROM patients WHERE id = ?");
  $emailQ->bind_param("i", $patient_id);
  $emailQ->execute();
  $pData = $emailQ->get_result()->fetch_assoc();
  $emailQ->close();

  if ($pData && !empty($pData['email'])) {
    $patient_email = $pData['email'];
    $patient_name = $pData['full_name'];
    $subject = "New Medical Report Added";
    $message = "Dear $patient_name,\n\nA new report has been added by Dr. $doctor_name.\nPlease log in to your account to view the full details.\n\nKind regards,\nHMS Team";
    sendEmail($patient_email, $subject, $message);
  }
}

$stmt->close();
$conn->close();
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
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
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
      const rows = document.querySelectorAll("#reportsTable tbody tr");

      rows.forEach(row => {
        const name = (row.children[1]?.innerText || "").toLowerCase();
        const date = row.children[4]?.innerText || "";
        const matchName = name.includes(searchValue);
        const matchDate = !filterDate || date === filterDate;
        row.style.display = (matchName && matchDate) ? "" : "none";
      });
    }

    // 🖨️ Print Reports
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

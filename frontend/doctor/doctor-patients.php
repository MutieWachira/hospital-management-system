<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
include '../../backend/db_connect.php';

// Restrict access to doctors only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
  header("Location: ../login.php");
  exit;
}

// Fetch patients assigned to this doctor
$doctor_id = $_SESSION['user_id']; 
$query = "SELECT * FROM patients WHERE doctor = (SELECT full_name FROM doctors WHERE id = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Patients - Doctor Portal</title>
  <link rel="stylesheet" href="css/doctor-patients.css">
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <h2>Doctor Panel</h2>
    <ul>
      <li><a href="doctor-dashboard.php">Dashboard</a></li>
      <li><a href="doctor-patients.php" class="active">My Patients</a></li>
      <li><a href="doctor-appointments.php">My Appointments</a></li>
      <li><a href="doctor-profile.php">Profile</a></li>
      <li><a href="doctor-reports.php">Reports</a></li>
      <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
    </ul>
  </aside>

  <!-- Main Section -->
  <main class="main">
    <div class="topbar">
      <h2>My Patients</h2>
      <input type="text" id="searchInput" placeholder="Search patients..." class="search-box" onkeyup="searchPatients()">
      <button class="add-btn" onclick="window.location.href='doctor-add-patient.php'">+ Add Patient</button>
    </div>

    <section class="patients-section">
      <table id="patientsTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['full_name']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['age']); ?></td>
                <td><?= htmlspecialchars($row['gender']); ?></td>
                <td>
                  <a href="doctor-patient-details.php?id=<?= $row['id']; ?>" class="view-btn">View</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No patients found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>
  </main>

  <script>
    function searchPatients() {
      const input = document.getElementById("searchInput").value.toLowerCase();
      const rows = document.querySelectorAll("#patientsTable tbody tr");
      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(input) ? "" : "none";
      });
    }
  </script>
</body>
</html>

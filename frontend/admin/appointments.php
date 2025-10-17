<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: /../frontend/login.html");
  exit;
}

// Fetch all appointments
$sql = "SELECT * FROM appointments ORDER BY id DESC";
$result = $conn->query($sql);

$search = $_GET['search'] ?? '';

// If search is not empty, filter results
if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT * FROM appointments 
        WHERE patient_name LIKE ? 
        OR doctor_name LIKE ?
        OR patient_email LIKE ?
        ORDER BY id DESC
    ");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM appointments ORDER BY id DESC");
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointment Management - HMS Admin</title>
  <link rel="stylesheet" href="css/appointment.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="doctor.php">Doctors</a></li>
        <li><a href="appointments.php" class="active">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
        <li><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="content">
      <header class="topbar">
        <h1>Appointment Management</h1>
        <div class="top-actions">
          <form method="GET" class="search-form">
            <input 
              type="text" 
              name="search" 
              placeholder="🔍 Search by name or email..." 
              value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
            />
          </form>
          <a href="add-appointment.php" class="add-btn">+ New Appointment</a>
        </div>
      </header>

      <!-- Appointment Table -->
      <section class="appointment-table">
        <h2>All Appointments</h2>
        <table>
          <thead>
            <tr>
              <th>Patient Name</th>
              <th>Doctor Name</th>
              <th>Patient Email</th>
              <th>Appointment Date</th>
              <th>Appointment Time</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['patient_name']) . "</td>
                        <td>" . htmlspecialchars($row['doctor_name']) . "</td>
                        <td>" . htmlspecialchars($row['patient_email']) . "</td>
                        <td>" . htmlspecialchars($row['appointment_date']) . "</td>
                        <td>" . htmlspecialchars($row['appointment_time']) . "</td>
                        <td>" . htmlspecialchars($row['status']) . "</td>
                        <td>
                          <a href='edit-appointment.php?id=" . $row['id'] . "' class='edit-btn'>Edit</a>
                          <a href='delete-appointment.php?id=" . $row['id'] . "' class='delete-btn' 
   onclick=\"return confirm('Are you sure you want to delete this appointment?')\">Delete</a>
                        </td>
                      </tr>";
              }
            } else {
              echo "<tr><td colspan='10' style='text-align:center;'>No doctors found</td></tr>";
            }

            $conn->close();
            ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>

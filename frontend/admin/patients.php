<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';

// Fetch all patients
$sql = "SELECT * FROM patients ORDER BY id DESC";
$result = $conn->query($sql);

$search = $_GET['search'] ?? '';

// If search is not empty, filter results
if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT * FROM patients 
        WHERE full_name LIKE ? 
        OR phone LIKE ? 
        OR doctor LIKE ?
        ORDER BY id DESC
    ");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM patients ORDER BY id DESC");
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patient Management - HMS Admin</title>
  <link rel="stylesheet" href="css/patients.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="patients.php" class="active">Patients</a></li>
        <li><a href="doctor.php">Doctors</a></li>
        <li><a href="appointments.php">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="content">
      <header class="topbar">
        <h1>Patient Management</h1>
        <div class="top-actions">
          <form method="GET" class="search-form">
            <input 
              type="text" 
              name="search" 
              placeholder="🔍 Search by name or email..." 
              value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
            />
          </form>
          <a href="add-patient.php" class="add-btn">+ Add New Patient</a>
        </div>
      </header>

      <!-- Patient Table -->
      <section class="patients-table">
        <h2>All Patients</h2>
        <table>
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Email</th>
              <th>Age</th>
              <th>Gender</th>
              <th>Phone</th>
              <th>Doctor</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['full_name']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['age']) . "</td>
                        <td>" . htmlspecialchars($row['gender']) . "</td>
                        <td>" . htmlspecialchars($row['phone']) . "</td>
                        <td>" . htmlspecialchars($row['doctor']) . "</td>
                        <td>
                          <a href='edit-patient.php?id=" . $row['id'] . "' class='edit-btn'>Edit</a>
                          <a href='delete-patient.php?id=" . $row['id'] . "' class='delete-btn' 
   onclick=\"return confirm('Are you sure you want to delete this patient?')\">Delete</a>
                        </td>
                      </tr>";
              }
            } else {
              echo "<tr><td colspan='8' style='text-align:center;'>No patients found</td></tr>";
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

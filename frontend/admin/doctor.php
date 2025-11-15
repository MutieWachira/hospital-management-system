<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
  exit;
}

// Fetch all doctors
$sql = "SELECT * FROM doctors ORDER BY id DESC";
$result = $conn->query($sql);

$search = $_GET['search'] ?? '';

// If search is not empty, filter results
if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT * FROM doctors 
        WHERE full_name LIKE ? 
        OR email LIKE ?
        OR department LIKE ?
        OR phone LIKE ? 
        ORDER BY id DESC
    ");
    $like = "%$search%";
    $stmt->bind_param("ssss", $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM doctors ORDER BY id DESC");
}

$stmt->execute();
$result = $stmt->get_result();

//function to calculate age from date of birth
function calculateAge($dob) {
  $today = new DateTime();
  $birthDate = new DateTime($dob);
  return $today->diff($birthDate)->y;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doctor Management - HMS Admin</title>
  <link rel="stylesheet" href="css/doctor.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="patients.php">Patients</a></li>
        <li><a href="doctor.php" class="active">Doctors</a></li>
        <li><a href="appointments.php">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
        <li><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="content">
      <header class="topbar">
        <h1>Doctor Management</h1>
        <div class="top-actions">
          <form method="GET" class="search-form">
            <input 
              type="text" 
              name="search" 
              placeholder="🔍 Search by name or email..." 
              value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
            />
          </form>
          <a href="add-doc.php" class="add-btn">+ Add New Doctor</a>
        </div>
      </header>

      <!-- Doctor Table -->
      <section class="doctors-table">
        <h2>All Doctors</h2>
        <table>
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Email</th>
              <th>Date of Birth</th>
              <th>Age</th>
              <th>Gender</th>
              <th>Department</th>
              <th>Phone</th>
              <th>Status</th>
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
                        <td>" . htmlspecialchars($row['d_o_b']) . "</td>
                        <td>" . calculateAge($row['d_o_b']) . " years</td>
                        <td>" . htmlspecialchars($row['gender']) . "</td>
                        <td>" . (isset($row['department']) ? htmlspecialchars($row['department']) : 'N/A') . "</td>
                        <td>" . htmlspecialchars($row['phone']) . "</td>
                        <td>" . htmlspecialchars($row['status']) . "</td>
                        <td>
                          <a href='edit-doctor.php?id=" . $row['id'] . "' class='edit-btn'>Edit</a>
                          <a href='delete-doctor.php?id=" . $row['id'] . "' class='delete-btn' 
   onclick=\"return confirm('Are you sure you want to delete this doctor?')\">Delete</a>
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

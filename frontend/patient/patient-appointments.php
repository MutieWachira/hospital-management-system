<?php
// Show errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
session_start();

// Check if logged in as patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header('Location: ../../frontend/login.html');
  exit;
}

$patient_id = (int) $_SESSION['user_id'];
$message = '';

// Fetch patient name/email for auto-fill (always run so form shows name on GET)
$patient_name = '';
$patient_email = '';
if ($stmt = $conn->prepare("SELECT full_name, email FROM patients WHERE id = ?")) {
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $patient_name = $row['full_name'];
    $patient_email = $row['email'];
  }
  $stmt->close();
}

// Handle new appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
  $doctor_id = (int) ($_POST['doctor_id'] ?? 0);
  $date = trim($_POST['date'] ?? '');
  $time = trim($_POST['time'] ?? '');
  $description = trim($_POST['description'] ?? '');

  // basic validation
  if ($doctor_id <= 0 || $date === '' || $time === '' || $description === '') {
    $message = "Please fill in all required fields.";
  } else {
    // check doctor availability for the selected slot (ignore cancelled/rejected)
    $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('Cancelled','Rejected','cancelled','rejected')");
    if ($chk) {
      $chk->bind_param("iss", $doctor_id, $date, $time);
      $chk->execute();
      $res = $chk->get_result();
      $busy = ($res->fetch_assoc()['cnt'] ?? 0) > 0;
      $chk->close();
    } else {
      $message = "Availability check failed: " . $conn->error;
      $busy = true;
    }

    if ($busy) {
      $message = "Selected doctor is not available at that date/time. Please choose another slot.";
    } else {
        // Fetch doctor name from doctors table
        $doctor_name = '';
        if ($dstmt = $conn->prepare("SELECT full_name FROM doctors WHERE id = ?")) {
            $dstmt->bind_param("i", $doctor_id);
            $dstmt->execute();
            $dres = $dstmt->get_result();
            if ($drow = $dres->fetch_assoc()) {
                $doctor_name = $drow['full_name'];
            }
            $dstmt->close();
        }

        // Prepare INSERT with correct placeholders (9 placeholders) and status as a parameter
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, patient_name, patient_email, doctor_name, appointment_date, appointment_time, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $status = 'pending'; // use the same casing/value as stored in your DB
            $stmt->bind_param("iisssssss", $patient_id, $doctor_id, $patient_name, $patient_email, $doctor_name, $date, $time, $description, $status);
            if ($stmt->execute()) {
                // success - redirect to avoid form resubmission
                header("Location: patient-appointments.php?added=1");
                exit;
            } else {
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Prepare failed: " . $conn->error;
        }
    }
  }
}

// Handle cancellation
if (isset($_GET['cancel_id'])) {
  $cancel_id = (int) $_GET['cancel_id'];
  $stmt = $conn->prepare("UPDATE appointments SET status='Cancelled' WHERE id=? AND patient_id=?");
  if ($stmt) {
    $stmt->bind_param("ii", $cancel_id, $patient_id);
    $stmt->execute();
    $stmt->close();
    $message = "Appointment cancelled successfully.";
  } else {
    $message = "Unable to cancel appointment: " . $conn->error;
  }
}

// Fetch all doctors for dropdown
$doctors = $conn->query("SELECT id, full_name, department FROM doctors");

// Fetch all appointments for this patient (consistent column aliases used in the template)
$stmt = $conn->prepare("
  SELECT a.id, d.full_name AS doctor_name, d.department AS specialty, a.appointment_date AS date, a.appointment_time AS time, a.status
  FROM appointments a
  INNER JOIN doctors d ON a.doctor_id = d.id
  WHERE a.patient_id = ?
  ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$appointments = [];
if ($stmt) {
  $stmt->bind_param("i", $patient_id);
  $stmt->execute();
  $appointments = $stmt->get_result();
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Appointments - HMS</title>
  <link rel="stylesheet" href="css/patient-appointments.css">
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Patient Panel</h2>
      <ul>
        <li><a href="patient-dashboard.php">Dashboard</a></li>
        <li><a href="patient-appointments.php" class="active">Appointments</a></li>
        <li><a href="patient-reports.php">My Reports</a></li>
        <li><a href="patient-profile.php">Profile</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h1>My Appointments</h1>
      </header>

      <?php if (!empty($message)): ?>
        <p class="message"><?= htmlspecialchars($message); ?></p>
      <?php endif; ?>

      <!-- Appointment Booking Form -->
      <section class="book-appointment">
        <h2>Book New Appointment</h2>
        <form method="POST">
          <div class="form-group">
            <label>Your Name:</label>
            <input type="text" name="patient_name_display" value="<?= htmlspecialchars($patient_name); ?>" readonly />
            <input type="hidden" name="patient_name" value="<?= htmlspecialchars($patient_name); ?>" />
            <input type="hidden" name="patient_email" value="<?= htmlspecialchars($patient_email); ?>" />
          </div>

          <div class="form-group">
            <label>Choose Doctor:</label>
            <select name="doctor_id" required>
              <option value="">-- Select Doctor --</option>
              <?php if ($doctors): while ($d = $doctors->fetch_assoc()): ?>
                <option value="<?= (int)$d['id']; ?>">
                  <?= htmlspecialchars($d['full_name']); ?> (<?= htmlspecialchars($d['department']); ?>)
                </option>
              <?php endwhile; endif; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Select Date:</label>
            <input type="date" name="date" required min="<?= date('Y-m-d'); ?>">
          </div>

          <div class="form-group">
            <label>Select Time:</label>
            <input type="time" name="time" required>
          </div>

          <div class="form-group">
            <label>Description:</label>
            <input type="text" name="description" required>
          </div>

          <button type="submit" name="book_appointment">Book Appointment</button>
        </form>
      </section>

      <!-- Appointment List -->
      <section class="appointments">
        <h2>My Appointment History</h2>
        <table>
          <thead>
            <tr>
              <th>Doctor</th>
              <th>Specialty</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($appointments && $appointments->num_rows > 0): ?>
              <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($a['doctor_name']); ?></td>
                  <td><?= htmlspecialchars($a['specialty']); ?></td>
                  <td><?= htmlspecialchars($a['date']); ?></td>
                  <td><?= htmlspecialchars($a['time']); ?></td>
                  <td><span class="status <?= strtolower($a['status']); ?>"><?= htmlspecialchars($a['status']); ?></span></td>
                  <td>
                    <?php if (strcasecmp($a['status'], 'Pending') === 0 || strcasecmp($a['status'], 'pending') === 0): ?>
                      <a href="?cancel_id=<?= (int)$a['id']; ?>" class="cancel">Cancel</a>
                    <?php else: ?>
                      <span class="disabled">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="no-data">No appointments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>

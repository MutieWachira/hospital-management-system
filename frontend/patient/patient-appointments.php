<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
require_once '../../backend/email_helper.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header("Location: ../login.html");
  exit;
}

$patient_id = (int)$_SESSION['user_id'];
$message = '';

// get patient info
$patient_name = '';
$patient_email = '';
$stmt = $conn->prepare("SELECT full_name, email FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $patient_name = $row['full_name'];
  $patient_email = $row['email'];
}
$stmt->close();

// book appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
  $doctor_id = (int)($_POST['doctor_id'] ?? 0);
  $date = trim($_POST['date'] ?? '');
  $time = trim($_POST['time'] ?? '');
  $desc = trim($_POST['description'] ?? '');

  if ($doctor_id && $date && $time && $desc) {
    // lookup doctor name by id
    $dname = '';
    $dstmt = $conn->prepare("SELECT full_name, email FROM doctors WHERE id = ?");
    if ($dstmt) {
      $dstmt->bind_param("i", $doctor_id);
      $dstmt->execute();
      $dres = $dstmt->get_result();
      if ($drow = $dres->fetch_assoc()) {
        $dname = $drow['full_name'];
        $doctor_email = $drow['email'];
      }
      $dstmt->close();
    }

    if ($dname === '') {
      $message = "Selected doctor not found.";
    } else {
      // check slot availability
      $chk = $conn->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE LOWER(TRIM(doctor_name)) = LOWER(TRIM(?)) AND appointment_date = ? AND appointment_time = ? AND LOWER(status) NOT IN ('cancelled','rejected')");
      if ($chk) {
        $chk->bind_param("sss", $dname, $date, $time);
        $chk->execute();
        $cres = $chk->get_result();
        $busy = ($cres->fetch_assoc()['cnt'] ?? 0) > 0;
        $chk->close();
      } else {
        $message = "Availability check failed: " . htmlspecialchars($conn->error);
        $busy = true;
      }

      if ($busy) {
        $message = "That time slot is already booked. Choose another.";
      } else {
        // insert new appointment
        $status = 'Pending';
        $stmt = $conn->prepare("INSERT INTO appointments (patient_name, patient_email, doctor_name, appointment_date, appointment_time, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
          $stmt->bind_param("sssssss", $patient_name, $patient_email, $dname, $date, $time, $desc, $status);
          if ($stmt->execute()) {

            // --- SEND EMAILS HERE ---
            if (!empty($doctor_email)) {
              $doctorMsg = "Dear Dr. $dname,\n\nYou have a new appointment with $patient_name on $date at $time.\n\nDescription: $desc\n\nHospital Management System";
              sendEmail($doctor_email, "New Appointment Booked", $doctorMsg);
            }

            $patientMsg = "Dear $patient_name,\n\nYour appointment with Dr. $dname on $date at $time has been successfully booked.\n\nWe will notify you once the doctor confirms.\n\nThank you,\nHospital Management System";
            sendEmail($patient_email, "Appointment Confirmation", $patientMsg);

            header("Location: patient-appointments.php?added=1");
            exit;
          } else {
            $message = "Error saving appointment: " . htmlspecialchars($stmt->error);
          }
          $stmt->close();
        } else {
          $message = "Prepare failed: " . htmlspecialchars($conn->error);
        }
      }
    }
  } else {
    $message = "Please fill all fields.";
  }
}

// cancel appointment
if (isset($_GET['cancel_id'])) {
  $id = (int)($_GET['cancel_id']);
  $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND patient_email = ?");
  if ($stmt) {
    $stmt->bind_param("is", $id, $patient_email);
    $stmt->execute();
    $stmt->close();
    $message = "Appointment cancelled.";
  } else {
    $message = "Unable to cancel appointment: " . htmlspecialchars($conn->error);
  }
}

// fetch doctors
$doctors = $conn->query("SELECT id, full_name, department FROM doctors");

// fetch appointments
$stmt = $conn->prepare("
  SELECT a.id, a.doctor_name AS doctor_name, COALESCE(d.department, '') AS specialty, a.appointment_date AS date, a.appointment_time AS time, a.status
  FROM appointments a
  LEFT JOIN doctors d ON TRIM(d.full_name) = TRIM(a.doctor_name)
  WHERE a.patient_email = ?
  ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
if ($stmt) {
  $stmt->bind_param("s", $patient_email);
  $stmt->execute();
  $appointments = $stmt->get_result();
  $stmt->close();
} else {
  $appointments = $conn->query("SELECT id, doctor_name, appointment_date AS date, appointment_time AS time, status FROM appointments WHERE patient_email = '" . $conn->real_escape_string($patient_email) . "' ORDER BY appointment_date DESC, appointment_time DESC");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Appointments - Patient Panel</title>
  <link rel="stylesheet" href="css/patient-appointments.css">
  <style>
    .status.pending { color: orange; font-weight: bold; }
    .status.accepted { color: green; font-weight: bold; }
    .status.cancelled { color: gray; font-weight: bold; }
    .status.finished { color: blue; font-weight: bold; }
  </style>
</head>
<body>
  <div class="container">
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

    <main class="content">
      <header class="topbar"><h1>My Appointments</h1></header>

      <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message); ?></p>
      <?php endif; ?>

      <section class="book-appointment">
        <h2>Book Appointment</h2>
        <form method="POST">
          <input type="hidden" name="book_appointment" value="1">
          <div class="form-group">
            <label>Choose Doctor:</label>
            <select name="doctor_id" required>
              <option value="">-- Select Doctor --</option>
              <?php while ($d = $doctors->fetch_assoc()): ?>
                <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['full_name']) ?> (<?= htmlspecialchars($d['department']); ?>)</option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group"><label>Date:</label><input type="date" name="date" min="<?= date('Y-m-d'); ?>" required></div>
          <div class="form-group"><label>Time:</label><input type="time" name="time" required></div>
          <div class="form-group"><label>Description:</label><input type="text" name="description" required></div>

          <button type="submit">Book Appointment</button>
        </form>
      </section>

      <section class="appointments">
        <h2>Appointment History</h2>
        <table>
          <thead>
            <tr>
              <th>Doctor</th><th>Specialty</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($appointments->num_rows > 0): ?>
              <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($a['doctor_name']); ?></td>
                  <td><?= htmlspecialchars($a['specialty']); ?></td>
                  <td><?= htmlspecialchars($a['date']); ?></td>
                  <td><?= htmlspecialchars($a['time']); ?></td>
                  <td><span class="status <?= strtolower($a['status']); ?>"><?= htmlspecialchars($a['status']); ?></span></td>
                  <td>
                    <?php if (strcasecmp($a['status'], 'Pending') === 0): ?>
                      <a href="?cancel_id=<?= $a['id']; ?>" class="cancel">Cancel</a>
                    <?php else: ?>
                      <span class="disabled">N/A</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" style="text-align:center;">No appointments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>

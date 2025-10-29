<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../backend/db_connect.php');
date_default_timezone_set('Africa/Nairobi');
session_start();

// ✅ Require doctor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
  header('Location: ../../frontend/login.html');
  exit;
}

// ✅ Fetch patient details
if (isset($_GET['id'])) {
  $patientID = (int)$_GET['id'];
  $query = "SELECT * FROM patients WHERE id = '$patientID'";
  $result = mysqli_query($conn, $query);
  $patient = mysqli_fetch_assoc($result);
}

// ✅ Fetch medical records
$records = [];
if (isset($patientID)) {
  $recordQuery = "SELECT * FROM medical_records WHERE patient_id = '$patientID' ORDER BY date DESC";
  $recordResult = mysqli_query($conn, $recordQuery);
  while ($row = mysqli_fetch_assoc($recordResult)) {
    $records[] = $row;
  }
}

// ✅ Handle add medical record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
  $date = $_POST['recordDate'];
  $diagnosis = $_POST['diagnosis'];
  $treatment = $_POST['treatment'];
  $notes = $_POST['doctorNotes'];

  $insert = "INSERT INTO medical_records (patient_id, date, diagnosis, treatment, doctor_notes)
             VALUES ('$patientID', '$date', '$diagnosis', '$treatment', '$notes')";
  mysqli_query($conn, $insert);
  header("Location: doctor-patient-details.php?id=$patientID");
  exit();
}

// ✅ Handle add prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
  $drug = $_POST['drugName'];
  $dosage = $_POST['dosage'];
  $duration = $_POST['duration'];
  $today = date('Y-m-d');

  $insertPrescription = "INSERT INTO prescriptions (patient_id, drug_name, dosage, duration, date_added)
                         VALUES ('$patientID', '$drug', '$dosage', '$duration', '$today')";
  mysqli_query($conn, $insertPrescription);
  header("Location: doctor-patient-details.php?id=$patientID");
  exit();
}

// ✅ Fetch today's and all prescriptions
$today = date('Y-m-d');
$prescriptionsToday = mysqli_query($conn, "SELECT * FROM prescriptions WHERE patient_id='$patientID' AND date_added='$today' ORDER BY id DESC");
$prescriptionsAll = mysqli_query($conn, "SELECT * FROM prescriptions WHERE patient_id='$patientID' ORDER BY date_added DESC");

// ✅ Function to calculate age
function calculateAge($dob) {
  if (!$dob) return '-';
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
  <title>View Patient - Doctor Panel</title>
  <link rel="stylesheet" href="css/doctor-patient-details.css" />
</head>
<body>
  <div class="container">
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

    <!-- Main Content -->
    <main class="content">
      <header class="patient-header">
        <div>
          <h1><?= htmlspecialchars($patient['full_name'] ?? 'Unknown'); ?></h1>
          <p>Email: <span><?= htmlspecialchars($patient['email'] ?? 'N/A'); ?></span></p>
          <p>Blood Group: <span><?= htmlspecialchars($patient['blood_group'] ?? '-'); ?></span></p>
        </div>
      </header>

      <!-- Personal Info -->
      <section class="patient-info">
        <h2>Personal Information</h2>
        <p>Age: <span><?= calculateAge($patient['d_o_b'] ?? '') . " years"; ?></span></p>
        <p>Gender: <span><?= htmlspecialchars($patient['gender'] ?? '-'); ?></span></p>
        <p>Phone: <span><?= htmlspecialchars($patient['phone'] ?? '-'); ?></span></p>
        <p>Address: <span><?= htmlspecialchars($patient['address'] ?? '-'); ?></span></p>
      </section>

      <!-- Medical Records -->
      <section class="medical-records">
        <h2>Medical Records</h2>
        <table>
          <thead>
            <tr><th>Date</th><th>Diagnosis</th><th>Treatment</th><th>Doctor Notes</th></tr>
          </thead>
          <tbody>
            <?php if (count($records) > 0): ?>
              <?php foreach ($records as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['date']); ?></td>
                  <td><?= htmlspecialchars($r['diagnosis']); ?></td>
                  <td><?= htmlspecialchars($r['treatment']); ?></td>
                  <td><?= htmlspecialchars($r['doctor_notes']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;">No medical records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <!-- Add Medical Record -->
      <section class="add-record">
        <h2>Add New Medical Record</h2>
        <form method="POST">
          <input type="hidden" name="add_record" value="1" />
          <input type="date" name="recordDate" required />
          <input type="text" name="diagnosis" placeholder="Diagnosis" required />
          <input type="text" name="treatment" placeholder="Treatment" required />
          <input type="text" name="doctorNotes" placeholder="Doctor Notes" required />
          <button type="submit">Add Record</button>
        </form>
      </section>

      <!-- Prescriptions -->
      <section class="prescriptions" id="prescriptionSection">
        <div class="prescription-header">
          <h2>Prescriptions</h2>
          <div class="print-buttons">
            <button class="print-btn" onclick="printPrescription('today')">🖨 Print Today's</button>
            <button class="print-btn" onclick="printPrescription('all')">🖨 Print All</button>
          </div>
        </div>

        <!-- Today's Prescriptions -->
        <h3>Today's Prescriptions (<?= date('d M Y'); ?>)</h3>
        <table>
          <thead>
            <tr><th>Drug</th><th>Dosage</th><th>Duration</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($prescriptionsToday) > 0): ?>
              <?php while ($p = mysqli_fetch_assoc($prescriptionsToday)): ?>
                <tr>
                  <td><?= htmlspecialchars($p['drug_name']); ?></td>
                  <td><?= htmlspecialchars($p['dosage']); ?></td>
                  <td><?= htmlspecialchars($p['duration']); ?></td>
                  <td><?= htmlspecialchars($p['date_added']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;">No prescriptions for today.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- All Prescription History -->
        <h3>Prescription History</h3>
        <table id="allPrescriptionsTable">
          <thead>
            <tr><th>Drug</th><th>Dosage</th><th>Duration</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($prescriptionsAll) > 0): ?>
              <?php while ($p = mysqli_fetch_assoc($prescriptionsAll)): ?>
                <tr>
                  <td><?= htmlspecialchars($p['drug_name']); ?></td>
                  <td><?= htmlspecialchars($p['dosage']); ?></td>
                  <td><?= htmlspecialchars($p['duration']); ?></td>
                  <td><?= htmlspecialchars($p['date_added']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;">No prescription history found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

      <!-- Add Prescription -->
      <section class="add-prescription">
        <h3>Add New Prescription</h3>
        <form method="POST">
          <input type="hidden" name="add_prescription" value="1" />
          <input type="text" name="drugName" placeholder="Drug Name" required />
          <input type="text" name="dosage" placeholder="Dosage" required />
          <input type="text" name="duration" placeholder="Duration" required />
          <button type="submit">Add Prescription</button>
        </form>
      </section>
    </main>
  </div>

  <script>
    function printPrescription(type) {
      let content = '';

      if (type === 'today') {
        content = document.querySelector('.prescriptions table:nth-of-type(1)').outerHTML;
      } else {
        content = document.getElementById('allPrescriptionsTable').outerHTML;
      }

      const patientName = "<?= htmlspecialchars($patient['full_name'] ?? 'Patient'); ?>";
      const printWindow = window.open('', '', 'height=600,width=800');

      printWindow.document.write('<html><head><title>Prescription Print</title>');
      printWindow.document.write('<style>');
      printWindow.document.write(`
        body { font-family: Arial; padding: 20px; }
        h2 { text-align: center; color: #004080; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
      `);
      printWindow.document.write('</style></head><body>');
      printWindow.document.write(`<div class="header">
        <h2>${patientName} - ${type === 'today' ? "Today's Prescription" : "Prescription History"}</h2>
        <p>Date: <?= date('d M Y'); ?></p>
      </div>`);
      printWindow.document.write(content);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.print();
    }
  </script>
</body>
</html>

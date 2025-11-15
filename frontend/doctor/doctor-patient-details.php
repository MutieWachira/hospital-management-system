<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../backend/db_connect.php');
require_once('../../backend/email_helper.php'); // optional if you’ll notify patients by email
date_default_timezone_set('Africa/Nairobi');

session_start();

// Require doctor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.html");
    exit;
}

// Initialize vars
$patient = [];
$records = [];
$patientID = null;

// ✅ Fetch patient details securely
if (isset($_GET['id'])) {
    $patientID = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patientID);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();
}

// ✅ Fetch medical records
if ($patientID) {
    $stmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY date DESC");
    $stmt->bind_param("i", $patientID);
    $stmt->execute();
    $recordResult = $stmt->get_result();
    while ($row = $recordResult->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
}

// Handle add medical record + doctor_reports insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
  $date = $_POST['recordDate'];
  $diagnosis = $_POST['diagnosis'];
  $treatment = $_POST['treatment'];
  $notes = $_POST['doctorNotes'];

  // Automatically get doctor & patient names
  $doctor_id = $_SESSION['user_id'];
  $doctor_name = $_SESSION['name'] ?? 'Unknown Doctor';
  $patient_name = $patient['full_name'] ?? 'Unknown Patient';

  // ✅ Insert into medical_records (now includes doctor_name & patient_name)
  $insert = $conn->prepare("
    INSERT INTO medical_records 
    (patient_id, patient_name, doctor_name, date, diagnosis, treatment, doctor_notes)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $insert->bind_param("issssss", $patientID, $patient_name, $doctor_name, $date, $diagnosis, $treatment, $notes);
  $insert->execute();
  $insert->close();

  // ✅ Automatically insert into doctor_reports table (unchanged logic)
  $insertReport = $conn->prepare("
    INSERT INTO doctor_reports 
    (doctor_id, doctor_name, patient_id, patient_name, diagnosis, treatment, report_date, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $insertReport->bind_param(
    "isisssss",
    $doctor_id,
    $doctor_name,
    $patientID,
    $patient_name,
    $diagnosis,
    $treatment,
    $date,
    $notes
  );
  $insertReport->execute();
  $insertReport->close();

  header("Location: doctor-patient-details.php?id=$patientID");
  exit();
}

// Handle add prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
  $drug = trim($_POST['drugName']);
  $dosage = trim($_POST['dosage']);
  $duration = trim($_POST['duration']);
  $price = (int)($_POST['price'] ?? 0); // optional field, default 0

  $doctor_name = $_SESSION['name'] ?? 'Unknown Doctor';
  $patient_name = $patient['full_name'] ?? 'Unknown Patient';

  // ✅ Insert into prescriptions (matches your new table)
  $insertPrescription = $conn->prepare("
    INSERT INTO prescriptions 
    (patient_id, patient_name, doctor_name, drug_name, dosage, price, duration)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $insertPrescription->bind_param("issssis", $patientID, $patient_name, $doctor_name, $drug, $dosage, $price, $duration);
  $insertPrescription->execute();
  $insertPrescription->close();

  header("Location: doctor-patient-details.php?id=$patientID");
  exit();
}


// ✅ Fetch prescriptions (today + all)
$today = date('Y-m-d');
$prescriptionsToday = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id = ? AND date_added = ? ORDER BY id DESC");
$prescriptionsToday->bind_param("is", $patientID, $today);
$prescriptionsToday->execute();
$todayResult = $prescriptionsToday->get_result();

$prescriptionsAll = $conn->prepare("SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY date_added DESC");
$prescriptionsAll->bind_param("i", $patientID);
$prescriptionsAll->execute();
$allResult = $prescriptionsAll->get_result();

// ✅ Age Calculation
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

    <main class="content">
      <header class="patient-header">
        <div>
          <h1><?= htmlspecialchars($patient['full_name'] ?? 'Unknown'); ?></h1>
          <p>Email: <span><?= htmlspecialchars($patient['email'] ?? 'N/A'); ?></span></p>
          <p>Blood Group: <span><?= htmlspecialchars($patient['blood_group'] ?? '-'); ?></span></p>
        </div>
      </header>

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
          <thead><tr><th>Date</th><th>Diagnosis</th><th>Treatment</th><th>Doctor Notes</th></tr></thead>
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
            <button onclick="printPrescription('today')">🖨 Print Today's</button>
            <button onclick="printPrescription('all')">🖨 Print All</button>
          </div>
        </div>

        <h3>Today's Prescriptions (<?= date('d M Y'); ?>)</h3>
        <table>
          <thead><tr><th>Drug</th><th>Dosage</th><th>Price</th><th>Duration</th><th>Date</th></tr></thead>
          <tbody>
            <?php if ($todayResult->num_rows > 0): ?>
              <?php while ($p = $todayResult->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($p['drug_name']); ?></td>
                  <td><?= htmlspecialchars($p['dosage']); ?></td>
                  <td>Ksh<?= htmlspecialchars($p['price']); ?></td>
                  <td><?= htmlspecialchars($p['duration']); ?></td>
                  <td><?= htmlspecialchars($p['date_added']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;">No prescriptions for today.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <h3>Prescription History</h3>
        <table id="allPrescriptionsTable">
          <thead><tr><th>Drug</th><th>Dosage</th><th>Price</th><th>Duration</th><th>Date</th></tr></thead>
          <tbody>
            <?php if ($allResult->num_rows > 0): ?>
              <?php while ($p = $allResult->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($p['drug_name']); ?></td>
                  <td><?= htmlspecialchars($p['dosage']); ?></td>
                  <td>Ksh<?= htmlspecialchars($p['price']); ?></td>
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
    <input type="number" name="price" placeholder="Price (optional)" min="0" />
    <input type="text" name="duration" placeholder="Duration" required />
    <button type="submit">Add Prescription</button>
  </form>
</section>
    </main>
  </div>

  <script>
    function printPrescription(type) {
      let content = (type === 'today')
        ? document.querySelector('.prescriptions table:nth-of-type(1)').outerHTML
        : document.getElementById('allPrescriptionsTable').outerHTML;

      const patientName = "<?= htmlspecialchars($patient['full_name'] ?? 'Patient'); ?>";
      const printWindow = window.open('', '', 'height=600,width=800');

      printWindow.document.write(`
        <html><head><title>Prescription Print</title>
        <style>
          body { font-family: Arial; padding: 20px; }
          h2 { text-align: center; color: #004080; }
          table { width: 100%; border-collapse: collapse; margin-top: 15px; }
          th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
          th { background-color: #f2f2f2; }
        </style></head><body>
        <h2>${patientName} - ${type === 'today' ? "Today's Prescription" : "Prescription History"}</h2>
        <p>Date: <?= date('d M Y'); ?></p>
        ${content}
        </body></html>
      `);
      printWindow.document.close();
      printWindow.print();
    }
  </script>
</body>
</html>

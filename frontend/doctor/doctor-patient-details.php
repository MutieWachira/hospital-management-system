<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../backend/db_connect.php'); // database connection
date_default_timezone_set('Africa/Nairobi'); // adjust if needed

// Fetch patient details
if (isset($_GET['id'])) {
  $patientID = $_GET['id'];
  $query = "SELECT * FROM patients WHERE id = '$patientID'";
  $result = mysqli_query($conn, $query);
  $patient = mysqli_fetch_assoc($result);
}

// Fetch medical records
$records = [];
if (isset($patientID)) {
  $recordQuery = "SELECT * FROM medical_records WHERE patient_id = '$patientID'";
  $recordResult = mysqli_query($conn, $recordQuery);
  while ($row = mysqli_fetch_assoc($recordResult)) {
    $records[] = $row;
  }
}

// Handle add medical record
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

// Handle add prescription
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

// Fetch only today's prescriptions
$today = date('Y-m-d');
$prescriptionsToday = mysqli_query($conn, "SELECT * FROM prescriptions WHERE patient_id='$patientID' AND date_added='$today'");
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
        <li><a href="doctor-patients.php"class="active">My Patients</a></li>
        <li><a href="doctor-appointments.php">My Appointments</a></li>
        <li><a href="doctor-profile.php">Profile</a></li>
        <li><a href="doctor-reports.php">Reports</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="content">
      <header class="patient-header">
        <div>
          <h1><?php echo $patient['full_name'] ?? 'Unknown'; ?></h1>
          <p>ID: <span><?php echo $patient['id'] ?? 'N/A'; ?></span></p>
          <p>Blood Group: <span><?php echo $patient['blood_group'] ?? '-'; ?></span></p>
        </div>
      </header>

      <!-- Personal Info -->
      <section class="patient-info">
        <h2>Personal Information</h2>
        <p>Age: <span><?php echo $patient['age'] ?? '-'; ?></span></p>
        <p>Gender: <span><?php echo $patient['gender'] ?? '-'; ?></span></p>
        <p>Phone: <span><?php echo $patient['phone'] ?? '-'; ?></span></p>
        <p>Address: <span><?php echo $patient['address'] ?? '-'; ?></span></p>
      </section>

      <!-- Medical Records -->
      <section class="medical-records">
        <h2>Medical Records</h2>
        <table>
          <thead>
            <tr><th>Date</th><th>Diagnosis</th><th>Treatment</th><th>Doctor Notes</th></tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
              <tr>
                <td><?php echo $r['date']; ?></td>
                <td><?php echo $r['diagnosis']; ?></td>
                <td><?php echo $r['treatment']; ?></td>
                <td><?php echo $r['doctor_notes']; ?></td>
              </tr>
            <?php endforeach; ?>
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
          <h2>Today's Prescriptions (<?php echo date('d M Y'); ?>)</h2>
          <button class="print-btn" onclick="printPrescription()">🖨 Print Today's Prescription</button>
        </div>

        <table>
          <thead>
            <tr><th>Drug</th><th>Dosage</th><th>Duration</th></tr>
          </thead>
          <tbody>
            <?php while ($p = mysqli_fetch_assoc($prescriptionsToday)): ?>
              <tr>
                <td><?php echo $p['drug_name']; ?></td>
                <td><?php echo $p['dosage']; ?></td>
                <td><?php echo $p['duration']; ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </section>
      <section class="add-prescription">
          <!-- Add Prescription -->
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
    // Print only today's prescription section
    function printPrescription() {
      const section = document.getElementById('prescriptionSection').innerHTML;
      const printWindow = window.open('', '', 'height=600,width=800');
      printWindow.document.write('<html><head><title>Prescription</title>');
      printWindow.document.write('<style>');
      printWindow.document.write(`
        body { font-family: Arial; padding: 20px; }
        h2 { color: #004080; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .header { text-align: center; margin-bottom: 15px; }
      `);
      printWindow.document.write('</style></head><body>');
      printWindow.document.write(`<div class="header">
        <h2>Prescription for <?php echo $patient['fullname'] ?? 'Patient'; ?></h2>
        <p>Date: <?php echo date('d M Y'); ?></p>
      </div>`);
      printWindow.document.write(section);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.print();
    }
  </script>
</body>
</html>

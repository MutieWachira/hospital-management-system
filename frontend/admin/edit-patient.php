<?php
include '../../backend/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? null;
if (!$id) {
    die("No patient selected.");
}

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['patientName'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $bloodGroup = $_POST['bloodGroup'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $doctor = $_POST['doctor'];

    $stmt = $conn->prepare("UPDATE patients 
        SET full_name=?, age=?, gender=?, blood_group=?, phone=?, address=?, doctor=?
        WHERE id=?");
    $stmt->bind_param('sisssssi', $fullName, $age, $gender, $bloodGroup, $phone, $address, $doctor, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Patient updated successfully!'); window.location='patients.php';</script>";
    } else {
        echo "<p>Error updating patient: " . $stmt->error . "</p>";
    }
}

// Load patient data
$stmt = $conn->prepare("SELECT * FROM patients WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Patient - HMS</title>
  <link rel="stylesheet" href="css/patient-details.css">
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
      <li><a href="index.html">Dashboard</a></li>
      <li><a href="patients.php" class="active">Patients</a></li>
      <li><a href="doctor.html" >Doctors</a></li>
      <li><a href="appointments.html">Appointments</a></li>
      <li><a href="reports.html">Reports</a></li>
      <li><a href="settings.html">Profile</a></li>
    </ul>
    </aside>

    <main class="content">
      <header class="topbar"><h2>Edit Patient Details</h2></header>

      <section class="add-patient">
        <form method="POST">
          <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="patientName" value="<?= htmlspecialchars($patient['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Age:</label>
            <input type="number" name="age" value="<?= htmlspecialchars($patient['age']) ?>" required>
          </div>
          <div class="form-group">
            <label>Gender:</label>
            <select name="gender" required>
              <option <?= $patient['gender']=='Male'?'selected':'' ?>>Male</option>
              <option <?= $patient['gender']=='Female'?'selected':'' ?>>Female</option>
              <option <?= $patient['gender']=='Other'?'selected':'' ?>>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Blood Group:</label>
            <input type="text" name="bloodGroup" value="<?= htmlspecialchars($patient['blood_group']) ?>" required>
          </div>
          <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($patient['phone']) ?>" required>
          </div>
          <div class="form-group">
            <label>Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($patient['address']) ?>" required>
          </div>
          <div class="form-group">
            <label>Assigned Doctor:</label>
            <input type="text" name="doctor" value="<?= htmlspecialchars($patient['doctor']) ?>" required>
          </div>

          <button type="submit" class="submit-btn">Update Patient</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

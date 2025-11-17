<?php
include '../../backend/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
  exit;
}

// Fetch doctors for dropdown
$doctors = [];
$result = $conn->query("SELECT id, full_name, department FROM doctors WHERE status='On Call'");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
  }
}

require_once '../../backend/email_helper.php';

$subject = "Welcome to HMS";
$message = "Dear $full_name,\n\nYour account has been created.\nEmail: $email\nPlease log in to access your dashboard.\n\n- HMS Administration";
sendEmail($email, $subject, $message);


$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add New Patient - HMS</title>
  <link rel="stylesheet" href="css/patient-details.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>HMS Admin</h2>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="patients.php" class="active">Patients</a></li>
        <li><a href="doctor.php">Doctors</a></li>
        <li><a href="appointments.php">Appointments</a></li>
        <li><a href="reports.php">Reports</a></li>
        <li><a href="settings.php">Profile</a></li>
        <li class="logout"><a href="logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main -->
    <main class="content">
      <header class="topbar">
        <h2>Add New Patient</h2>
      </header>

      <section class="add-patient">
        <h2>Patient Registration Form</h2>
        <form method="POST" action="../../backend/add_patient.php" id="addPatientForm">
          
          <div class="form-group">
            <label for="patientName">Full Name:</label>
            <input type="text" id="patientName" name="full_name" placeholder="Enter full name" required />
          </div>

          <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter email" required />
          </div>

          <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="date_of_birth" required>
          </div>

          <div class="form-group">
            <label for="gender">Gender:</label>
            <select id="gender" name="gender" required>
              <option value="">Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="bloodGroup">Blood Group:</label>

            <!-- select + hidden value -> backend receives 'blood_group' -->
            <select id="bloodGroupSelect" required>
              <option value="">Select blood group</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
              <option value="O+">O+</option>
              <option value="O-">O-</option>
              <option value="Other">Other</option>
            </select>

            <!-- hidden input actually submitted -->
            <input type="hidden" id="bloodGroupInput" name="blood_group" />

            <!-- visible only when "Other" selected -->
            <input type="text" id="bloodGroupOther" placeholder="Specify blood group (e.g. Apos)" style="display:none;margin-top:8px;" />
          </div>

          <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" placeholder="+254 712 345 678" required />
          </div>

          <div class="form-group">
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" placeholder="Enter address" required />
          </div>

          <div class="form-group">
            <label for="doctor">Assigned Doctor:</label>
            <select id="doctor" name="doctor" required>
              <option value="">Select Doctor</option>
              <?php foreach ($doctors as $doc): ?>
                <option value="<?= htmlspecialchars($doc['full_name']) ?>">
                  <?= htmlspecialchars($doc['full_name'] . " — " . $doc['department']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 🔒 Password Fields -->
          <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter password"/>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirm Password:</label>
            <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter password"/>
          </div>

          <button type="submit" class="submit-btn" name="submit">Save Patient</button>
        </form>
      </section>
    </main>
  </div>

  <script>
    (function(){
      const sel = document.getElementById('bloodGroupSelect');
      const other = document.getElementById('bloodGroupOther');
      const hidden = document.getElementById('bloodGroupInput');

      function sync() {
        const v = sel.value;
        if (v === 'Other') {
          other.style.display = 'block';
          other.required = true;
          hidden.value = other.value.trim();
        } else {
          other.style.display = 'none';
          other.required = false;
          hidden.value = v;
        }
      }

      sel.addEventListener('change', sync);
      other.addEventListener('input', () => { hidden.value = other.value.trim(); });

      // initialize on load
      document.addEventListener('DOMContentLoaded', sync);
    })();
  </script>
</body>
</html>

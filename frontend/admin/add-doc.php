<?php
include '../../backend/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: /../frontend/login.html");
  exit;
}

require_once '../../backend/email_helper.php';

$subject = "Welcome to HMS";
$message = "Dear $full_name,\n\nYour account has been created.\nEmail: $email\nPlease log in to access your dashboard.\n\n- HMS Administration";
sendEmail($email, $subject, $message);


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add New Doctor - HMS</title>
  <link rel="stylesheet" href="css/add-doc.css" />
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
        <h2>Add New Doctor</h2>
      </header>

      <section class="add-doctor">
        <h2>Doctor Registration Form</h2>
        <form method="POST" action="../../backend/add_doctor.php" id="addDoctorForm">
          
          <div class="form-group">
            <label for="doctorName">Full Name:</label>
            <input type="text" id="doctorName" name="full_name" placeholder="Enter full name" required />
          </div>

          <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" placeholder="Enter email" required />
          </div>

          <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter password" required />
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required />
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
            <label for="department">Department:</label>
            <input type="text" name="department" id="department" placeholder="e.g. Cardiology" required />
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
            <label for="status">Status:</label>
            <select id="status" name="status" required>
              <option value="">Select Status</option>
              <option value="On Call">On Call</option>
              <option value="Off Call">Off Call</option>
              <option value="Leave">Leave</option>
            </select>
          </div>

          <button type="submit" class="submit-btn" name="submit">Save Doctor</button>
        </form>
      </section>
    </main>
  </div>

</body>
</html>

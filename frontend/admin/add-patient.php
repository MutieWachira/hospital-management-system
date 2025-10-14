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
        <li><a href="index.html">Dashboard</a></li>
      <li><a href="patients.php" class="active">Patients</a></li>
      <li><a href="doctor.html" >Doctors</a></li>
      <li><a href="appointments.html">Appointments</a></li>
      <li><a href="reports.html">Reports</a></li>
      <li><a href="settings.html">Profile</a></li>
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
            <input type="text" id="email" name="email" placeholder="Enter email" required />
          </div>

          <div class="form-group">
            <label for="age">Age:</label>
            <input type="number" id="age" name="age" placeholder="Enter age" required />
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
            <input type="text" name="blood_group" id="bloodGroup" placeholder="e.g. O+" required />
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
            <input type="text" id="doctor" name="doctor" placeholder="Enter doctor name" required />
          </div>

          <button type="submit"  class="submit-btn" name="submit">Save Patient</button>
        </form>
      </section>
    </main>
  </div>

</body>
</html>

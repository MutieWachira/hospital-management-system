<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Dashboard - HMS</title>
  <link rel="stylesheet" href="css/doctor-dashboard.css">
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <h2>Doctor Portal</h2>
    <ul>
      <li><a href="doctor-dashboard.php" class="active">Dashboard</a></li>
      <li><a href="doctor-patients.php">My Patients</a></li>
      <li><a href="doctor-appointments.php">My Appointments</a></li>
      <li><a href="doctor-profile.php">Profile</a></li>
    </ul>
  </aside>

  <!-- Main -->
  <main class="main">
    <div class="topbar">
      <h2>Welcome, Dr. Alice Kim</h2>
      <div class="topbar-right">
        <span class="material-symbols-outlined">notifications</span>
        <span class="material-symbols-outlined">account_circle</span>
      </div>
    </div>

    <section class="stats">
      <div class="card">
        <h3>Today’s Appointments</h3>
        <p>5</p>
      </div>
      <div class="card">
        <h3>Total Patients</h3>
        <p>32</p>
      </div>
      <div class="card">
        <h3>Pending Reports</h3>
        <p>2</p>
      </div>
    </section>

    <section class="appointments">
      <h2>Today’s Appointments</h2>
      <table>
        <thead>
          <tr>
            <th>Patient</th>
            <th>Time</th>
            <th>Reason</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>John Doe</td>
            <td>09:00 AM</td>
            <td>Fever & Cough</td>
            <td><span class="status upcoming">Upcoming</span></td>
          </tr>
          <tr>
            <td>Mary Wanjiku</td>
            <td>11:30 AM</td>
            <td>Follow-up</td>
            <td><span class="status done">Completed</span></td>
          </tr>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>

<?php
session_start();

// Require doctor login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
  header("Location: ../login.html");
  exit();
}

$doctor_id = (int) ($_SESSION['user_id'] ?? 0);

include '../../backend/db_connect.php';

// Fetch doctor's full name
$doctor_name = '';
if ($stmt = $conn->prepare("SELECT full_name FROM doctors WHERE id = ?")) {
  $stmt->bind_param("i", $doctor_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $doctor_name = trim($row['full_name']);
  }
  $stmt->close();
} else {
  error_log("Prepare failed (doctor lookup): " . $conn->error);
}

// Fetch appointments for this doctor
$appointments = [];
if ($doctor_name !== '') {
  $sql = "
      SELECT 
        id, 
        patient_name, 
        patient_email,
        appointment_date AS date, 
        appointment_time AS time, 
        status, 
        description AS notes
      FROM appointments
      WHERE LOWER(TRIM(doctor_name)) = LOWER(TRIM(?))
      ORDER BY appointment_date DESC, appointment_time DESC
  ";
  if ($astmt = $conn->prepare($sql)) {
    $astmt->bind_param("s", $doctor_name);
    $astmt->execute();
    $ares = $astmt->get_result();
    while ($r = $ares->fetch_assoc()) {
      $appointments[] = $r;
    }
    $astmt->close();
  } else {
    error_log("Prepare failed (appointments): " . $conn->error);
  }
} else {
  error_log("Doctor name not found for doctor_id: " . $doctor_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Appointments - Doctor Panel</title>
  <link rel="stylesheet" href="css/doctor-appointments.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div>
        <h2>Doctor Panel</h2>
        <ul>
          <li><a href="doctor-dashboard.php">Dashboard</a></li>
          <li><a href="doctor-patients.php">My Patients</a></li>
          <li><a href="doctor-appointments.php" class="active">My Appointments</a></li>
          <li><a href="doctor-profile.php">Profile</a></li>
          <li><a href="doctor-reports.php">Reports</a></li>
          <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
        </ul>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h1>My Appointments</h1>
        <input type="text" id="searchInput" class="search-bar" placeholder="Search patient..." />
      </header>

      <!-- Appointment List -->
      <section class="appointments-section">
        <table id="appointmentTable">
          <thead>
            <tr>
              <th>Patient Name</th>
              <th>Date</th>
              <th>Time</th>
              <th>Status</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($appointments)): ?>
              <?php foreach ($appointments as $app): ?>
                <?php
                  $rawStatus = $app['status'] ?? '';
                  $statusClass = preg_replace('/[^a-z0-9_-]/', '', strtolower($rawStatus));
                ?>
                <tr>
                  <td><?= htmlspecialchars($app['patient_name']); ?></td>
                  <td><?= htmlspecialchars($app['date']); ?></td>
                  <td><?= htmlspecialchars($app['time']); ?></td>
                  <td><span class="status <?= $statusClass; ?>"><?= htmlspecialchars($rawStatus); ?></span></td>
                  <td><?= htmlspecialchars($app['notes'] ?? '—'); ?></td>
                  <td>
                    <select class="status-select" 
                            data-id="<?= (int)$app['id']; ?>" 
                            data-email="<?= htmlspecialchars($app['patient_email']); ?>" 
                            data-patient="<?= htmlspecialchars($app['patient_name']); ?>"
                            data-date="<?= htmlspecialchars($app['date']); ?>">
                      <?php
                        $options = ['Pending', 'Ongoing', 'Accepted', 'Cancelled', 'Finished'];
                        $cur = $app['status'] ?? '';
                        foreach ($options as $opt) {
                          $sel = (strcasecmp($cur, $opt) === 0) ? 'selected' : '';
                          echo "<option value=\"" . htmlspecialchars($opt) . "\" $sel>" . htmlspecialchars($opt) . "</option>";
                        }
                      ?>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" style="text-align:center;">No appointments found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <script>
    const tableBody = document.querySelector("#appointmentTable tbody");
    const searchInput = document.getElementById("searchInput");

    // Search filter
    searchInput.addEventListener("keyup", () => {
      const filter = searchInput.value.toLowerCase();
      const rows = tableBody.getElementsByTagName("tr");
      for (let row of rows) {
        const name = (row.children[0]?.innerText || '').toLowerCase();
        row.style.display = name.includes(filter) ? "" : "none";
      }
    });

    // Handle status change
tableBody.addEventListener("change", async (e) => {
  if (e.target.classList.contains("status-select")) {
    const id = e.target.dataset.id;
    const newStatus = e.target.value;
    const patientEmail = e.target.dataset.email;
    const patientName = e.target.dataset.patient;
    const date = e.target.dataset.date;
    const row = e.target.closest("tr");
    const doctorName = "<?= htmlspecialchars($doctor_name); ?>"; // current doctor session name
    const time = row.children[2]?.innerText || '';

    try {
      const res = await fetch("../../backend/update_appointments.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body: new URLSearchParams({
          id, status: newStatus, email: patientEmail, 
          patient: patientName, date, time, doctor: doctorName
        })
      });

      const data = await res.json();

      if (data.success) {
        const statusSpan = e.target.closest("tr").querySelector(".status");
        statusSpan.textContent = newStatus;
        statusSpan.className = "status " + newStatus.toLowerCase().replace(/[^a-z0-9_-]/g,'');
        alert("Appointment status updated successfully and email notification sent!");
      } else {
        alert("Update failed: " + (data.error || "Unknown error"));
      }
    } catch (err) {
      console.error(err);
      alert("Unable to update status. Check console for details.");
    }
  }
});

  </script>
</body>
</html>

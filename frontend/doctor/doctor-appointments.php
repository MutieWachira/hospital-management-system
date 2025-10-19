<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../frontend/login.php");
  exit();
}
$doctor_id = $_SESSION['user_id'];
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
      <a href="../../backend/logout.php" class="logout-btn">Logout</a>
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
          <tbody></tbody>
        </table>
      </section>
    </main>
  </div>

  <script>
    const doctorId = <?php echo json_encode($doctor_id); ?>;
    const tableBody = document.querySelector("#appointmentTable tbody");
    const searchInput = document.getElementById("searchInput");

    // Fetch doctor appointments
    async function loadAppointments() {
      const res = await fetch("../../backend/get_appointments.php?doctor_id=" + doctorId);
      const data = await res.json();
      tableBody.innerHTML = "";

      if (data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan='6' style='text-align:center;'>No appointments found.</td></tr>`;
        return;
      }

      data.forEach(app => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${app.patient_name}</td>
          <td>${app.date}</td>
          <td>${app.time}</td>
          <td><span class="status ${app.status.toLowerCase()}">${app.status}</span></td>
          <td>${app.notes || "—"}</td>
          <td>
            <select class="status-select" data-id="${app.id}">
              <option value="Pending" ${app.status === "Pending" ? "selected" : ""}>Pending</option>
              <option value="Ongoing" ${app.status === "Ongoing" ? "selected" : ""}>Ongoing</option>
              <option value="Cancelled" ${app.status === "Cancelled" ? "selected" : ""}>Cancelled</option>
              <option value="Finished" ${app.status === "Finished" ? "selected" : ""}>Finished</option>
            </select>
          </td>
        `;
        tableBody.appendChild(row);
      });
    }

    loadAppointments();

    // Filter Search
    searchInput.addEventListener("keyup", () => {
      const filter = searchInput.value.toLowerCase();
      const rows = tableBody.getElementsByTagName("tr");
      for (let row of rows) {
        const name = row.children[0].innerText.toLowerCase();
        row.style.display = name.includes(filter) ? "" : "none";
      }
    });

    // Update Status on Change
    tableBody.addEventListener("change", async (e) => {
      if (e.target.classList.contains("status-select")) {
        const id = e.target.dataset.id;
        const newStatus = e.target.value;

        await fetch("../../backend/update_appointment.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id, status: newStatus })
        });

        const statusSpan = e.target.closest("tr").querySelector(".status");
        statusSpan.textContent = newStatus;
        statusSpan.className = "status " + newStatus.toLowerCase();
      }
    });
  </script>
</body>
</html>

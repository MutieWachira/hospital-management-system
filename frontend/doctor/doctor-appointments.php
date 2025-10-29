<?php
session_start();

// require doctor login and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
  header("Location: ../../frontend/login.html");
  exit();
}
$doctor_id = (int) ($_SESSION['user_id'] ?? 0);
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
    const doctorId = <?= json_encode((int)$doctor_id); ?>;
    const tableBody = document.querySelector("#appointmentTable tbody");
    const searchInput = document.getElementById("searchInput");

    // helper to create cell with text (prevents HTML injection)
    function tdText(text) {
      const td = document.createElement('td');
      td.textContent = text ?? '';
      return td;
    }

    // Fetch doctor appointments
    async function loadAppointments() {
      try {
        const res = await fetch("../../backend/get_appointments.php?doctor_id=" + encodeURIComponent(doctorId));
        if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
        const data = await res.json();
        tableBody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
          tableBody.innerHTML = `<tr><td colspan='6' style='text-align:center;'>No appointments found.</td></tr>`;
          return;
        }

        data.forEach(app => {
          const row = document.createElement("tr");

          row.appendChild(tdText(app.patient_name));
          row.appendChild(tdText(app.date));
          row.appendChild(tdText(app.time));

          const statusText = app.status ?? '';
          const statusTd = document.createElement('td');
          const statusSpan = document.createElement('span');
          statusSpan.className = 'status ' + (typeof statusText === 'string' ? statusText.toLowerCase() : '');
          statusSpan.textContent = statusText;
          statusTd.appendChild(statusSpan);
          row.appendChild(statusTd);

          row.appendChild(tdText(app.notes || '—'));

          const actionsTd = document.createElement('td');
          const select = document.createElement('select');
          select.className = 'status-select';
          select.dataset.id = String(app.id || '');
          ['Pending','Ongoing','Cancelled','Finished'].forEach(s => {
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = s;
            if (String(app.status) === s) opt.selected = true;
            select.appendChild(opt);
          });
          actionsTd.appendChild(select);
          row.appendChild(actionsTd);

          tableBody.appendChild(row);
        });
      } catch (err) {
        console.error(err);
        tableBody.innerHTML = `<tr><td colspan='6' style='text-align:center;color:#b00;'>Failed to load appointments.</td></tr>`;
      }
    }

    loadAppointments();

    // Filter Search
    searchInput.addEventListener("keyup", () => {
      const filter = searchInput.value.toLowerCase();
      const rows = tableBody.getElementsByTagName("tr");
      for (let row of rows) {
        const name = (row.children[0]?.innerText || '').toLowerCase();
        row.style.display = name.includes(filter) ? "" : "none";
      }
    });

    // Update Status on Change
    tableBody.addEventListener("change", async (e) => {
      if (e.target.classList.contains("status-select")) {
        const id = e.target.dataset.id;
        const newStatus = e.target.value;

        try {
          const res = await fetch("../../backend/update_appointment.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id, status: newStatus })
          });
          if (!res.ok) throw new Error('Update failed: ' + res.status);
          // reflect change in UI
          const statusSpan = e.target.closest("tr").querySelector(".status");
          if (statusSpan) {
            statusSpan.textContent = newStatus;
            statusSpan.className = "status " + newStatus.toLowerCase();
          }
        } catch (err) {
          console.error(err);
          alert('Unable to update status. Check console for details.');
        }
      }
    });
  </script>
</body>
</html>

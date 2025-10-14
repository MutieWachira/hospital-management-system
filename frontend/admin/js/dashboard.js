document.addEventListener("DOMContentLoaded", () => {
  fetch("http://localhost/hms/backend/get_data_admin_dashboard.php")
    .then(res => res.json())
    .then(data => {
      // Update the dashboard numbers
      document.getElementById("totalPatients").textContent = data.total_patients;
      document.getElementById("totalDoctors").textContent = data.total_doctors;
      document.getElementById("totalNurses").textContent = data.total_nurses;
      document.getElementById("appointmentsToday").textContent = data.appointments_today;
    })
    .catch(err => console.error("Error loading dashboard:", err));
});

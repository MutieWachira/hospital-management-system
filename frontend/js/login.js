// Hardcoded demo users with auto role detection
const users = [
  { role: "admin", email: "admin@hms.com", password: "admin123", redirect: "admin/index.html" },
  { role: "doctor", email: "doctor@hms.com", password: "doctor123", redirect: "doctor/doctor-dashboard.html" },
  { role: "patient", email: "patient@hms.com", password: "patient123", redirect: "patient/patient-dashboard.html" },
];

document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  // Search for matching user
  const user = users.find(u => u.email === email && u.password === password);

  if (user) {
    alert(`Welcome ${user.role.toUpperCase()}! Redirecting to your dashboard...`);
    window.location.href = user.redirect;
  } else {
    alert("Invalid email or password. Please try again.");
  }
});

document.getElementById("addPatientForm").addEventListener("submit", (e) => {
  e.preventDefault();

  const name = document.getElementById("patientName").value;
  const age = document.getElementById("age").value;
  const gender = document.getElementById("gender").value;
  const bloodGroup = document.getElementById("bloodGroup").value;
  const phone = document.getElementById("phone").value;
  const address = document.getElementById("address").value;
  const doctor = document.getElementById("doctor").value;

  // Temporary patient object (replace with Firebase later)
  const newPatient = {
    id: "P" + Math.floor(Math.random() * 100000),
    name,
    age,
    gender,
    bloodGroup,
    phone,
    address,
    doctor
  };

  console.log("🩺 New Patient Added:", newPatient);
  alert(`✅ Patient ${name} added successfully!`);

  // Redirect to patients list page
  window.location.href = "patients.html";
});

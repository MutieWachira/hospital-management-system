// ===== Select Elements =====
const recordForm = document.getElementById("recordForm");
const recordsTable = document.getElementById("recordsTable");
const editPatientBtn = document.getElementById("editPatientBtn");
const deletePatientBtn = document.getElementById("deletePatientBtn");

// ===== Add New Record =====
recordForm.addEventListener("submit", (e) => {
  e.preventDefault();

  // Get values
  const date = document.getElementById("recordDate").value;
  const diagnosis = document.getElementById("diagnosis").value;
  const treatment = document.getElementById("treatment").value;
  const doctor = document.getElementById("doctor").value;

  // Create new row
  const newRow = document.createElement("tr");
  newRow.innerHTML = `
    <td>${date}</td>
    <td>${diagnosis}</td>
    <td>${treatment}</td>
    <td>${doctor}</td>
    <td>
      <button class="edit-btn">Edit</button>
      <button class="delete-btn">Delete</button>
    </td>
  `;

  // Add to table
  recordsTable.appendChild(newRow);

  // Clear form
  recordForm.reset();

  alert("✅ Medical record added successfully!");
});

// ===== Edit Record =====
recordsTable.addEventListener("click", (e) => {
  if (e.target.classList.contains("edit-btn")) {
    const row = e.target.closest("tr");
    const cells = row.querySelectorAll("td");

    const date = prompt("Edit Date:", cells[0].textContent);
    const diagnosis = prompt("Edit Diagnosis:", cells[1].textContent);
    const treatment = prompt("Edit Treatment:", cells[2].textContent);
    const doctor = prompt("Edit Doctor:", cells[3].textContent);

    if (date && diagnosis && treatment && doctor) {
      cells[0].textContent = date;
      cells[1].textContent = diagnosis;
      cells[2].textContent = treatment;
      cells[3].textContent = doctor;
      alert("✅ Record updated successfully!");
    }
  }
});

// ===== Delete Record =====
recordsTable.addEventListener("click", (e) => {
  if (e.target.classList.contains("delete-btn")) {
    const row = e.target.closest("tr");
    if (confirm("⚠️ Are you sure you want to delete this record?")) {
      row.remove();
      alert("🗑️ Record deleted successfully.");
    }
  }
});

// ===== Edit Patient Info =====
editPatientBtn.addEventListener("click", () => {
  const nameEl = document.getElementById("patientName");
  const ageEl = document.getElementById("patientAge");
  const phoneEl = document.getElementById("phone");
  const addressEl = document.getElementById("address");

  const newName = prompt("Enter new name:", nameEl.textContent);
  const newAge = prompt("Enter new age:", ageEl.textContent);
  const newPhone = prompt("Enter new phone number:", phoneEl.textContent);
  const newAddress = prompt("Enter new address:", addressEl.textContent);

  if (newName) nameEl.textContent = newName;
  if (newAge) ageEl.textContent = newAge;
  if (newPhone) phoneEl.textContent = newPhone;
  if (newAddress) addressEl.textContent = newAddress;

  alert("✅ Patient details updated successfully!");
});

// ===== Delete Patient =====
deletePatientBtn.addEventListener("click", () => {
  if (confirm("⚠️ Are you sure you want to delete this patient and all records?")) {
    document.querySelector(".content").innerHTML = `
      <div class="delete-message">
        <h2>🗑️ Patient Record Deleted</h2>
        <p>The patient and all associated records have been removed.</p>
        <a href="patients.html" class="back-link">← Back to Patient List</a>
      </div>
    `;
  }
});

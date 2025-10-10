const form = document.getElementById("recordForm");
const recordsTable = document.getElementById("recordsTable");

form.addEventListener("submit", (e) => {
  e.preventDefault();
  const date = document.getElementById("recordDate").value;
  const diagnosis = document.getElementById("diagnosis").value;
  const treatment = document.getElementById("treatment").value;
  const doctor = document.getElementById("doctor").value;

  const newRow = document.createElement("tr");
  newRow.innerHTML = `
    <td>${date}</td>
    <td>${diagnosis}</td>
    <td>${treatment}</td>
    <td>${doctor}</td>
  `;
  recordsTable.appendChild(newRow);

  form.reset();
  alert("New record added successfully!");
});

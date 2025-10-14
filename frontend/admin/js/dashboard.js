  fetch("../../backend/fetch_patients.php")
      .then(response => response.json())
      .then(data => {
        const tbody = document.getElementById("patientList");
        data.forEach(patient => {
          const row = document.createElement("tr");
          row.innerHTML = `
            <td>${patient.id}</td>
            <td>${patient.full_name}</td>
            <td>${patient.email}</td>
            <td>${patient.age}</td>
            <td>${patient.gender}</td>
            <td>${patient.contact}</td>
            <td><button>Edit</button> <button>Delete</button></td>
          `;
          tbody.appendChild(row);
        });
      })
      .catch(err => console.error("Error fetching patients:", err));
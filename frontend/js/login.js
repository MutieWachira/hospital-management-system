document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  // Collect form input values
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  // Use FormData for sending normal POST data
  const formData = new FormData();
  formData.append("email", email);
  formData.append("password", password);

  try {
    // Adjust URL based on your file structure
    const response = await fetch("../backend/login.php", {
      method: "POST",
      body: formData, // ✅ send FormData instead of JSON
    });

    // Because PHP redirects on success, we check status
    if (response.redirected) {
      window.location.href = response.url;
      return;
    }

    // If PHP returns JSON or text (like an error)
    const resultText = await response.text();
    console.log(resultText);

    if (resultText.includes("Invalid")) {
      alert("❌ Invalid email or password.");
    }
  } catch (error) {
    console.error("Login error:", error);
    alert("⚠️ Unable to connect to server. Check your backend setup.");
  }
});

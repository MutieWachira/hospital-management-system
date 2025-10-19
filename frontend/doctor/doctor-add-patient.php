<?php
include('../../backend/db_connect.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// require doctor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../frontend/login.html');
    exit;
}

$doctor_name = $_SESSION['name'] ?? ''; // use doctor name stored in session

$error = $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // collect + sanitize
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');

    // basic validation
    if ($fullname === '' || $email === '' || $age <= 0 || $gender === '' || $phone === '' || $address === '') {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // check duplicate email
        $check = $conn->prepare("SELECT id FROM patients WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "A patient with that email already exists.";
            $check->close();
        } else {
            $check->close();
            // Insert patient record (include doctor column)
            $sql = "INSERT INTO patients (full_name, email, age, gender, phone, address, blood_group, doctor, role, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'patient', NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                // bind types: s (fullname), s (email), i (age), s (gender), s (phone), s (address), s (blood_group), s (doctor)
                $stmt->bind_param("ssisssss", $fullname, $email, $age, $gender, $phone, $address, $blood_group, $doctor_name);

                if ($stmt->execute()) {
                    $success = "Patient added successfully!";
                    // redirect to patients list (optional)
                    header("Location: doctor-patients.php?added=1");
                    exit;
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Patient - Doctor Panel</title>
  <link rel="stylesheet" href="css/doctor-add-patient.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Doctor Panel</h2>
      <ul>
        <li><a href="doctor-dashboard.php">Dashboard</a></li>
        <li><a href="doctor-patients.php" class="active">My Patients</a></li>
        <li><a href="doctor-appointments.php">Appointments</a></li>
        <li><a href="doctor-profile.php">Profile</a></li>
        <li><a href="doctor-reports.php">Reports</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
        
      </ul>
    </aside>

    <!-- Main -->
    <main class="main">
      <header class="topbar">
        <h2>Add New Patient</h2>
      </header>

      <section class="form-section">
        <?php if (!empty($error)): ?>
          <p class="error"><?php echo $error; ?></p>
        <?php elseif (!empty($success)): ?>
          <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form method="POST" class="add-patient-form">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" placeholder="Enter full name" required />
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter email" required />
          </div>

          <div class="form-group">
            <label>Age</label>
            <input type="number" name="age" placeholder="Enter age" required />
          </div>

          <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
              <option value="">Select gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>

          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" placeholder="+254..." required />
          </div>

          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" placeholder="Enter address" required />
          </div>

          <div class="form-group">
            <label>Blood Group</label>
            <select name="blood_group">
              <option value="">Select</option>
              <option value="O+">O+</option>
              <option value="O-">O-</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
            </select>
          </div>

          <button type="submit" class="btn">Add Patient</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

<?php
// ✅ Enable errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../../backend/db_connect.php';
session_start();

// ✅ Require patient login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
  header('Location: ../../frontend/login.html');
  exit;
}

$patient_id = (int) $_SESSION['user_id']; // cast to int

// ✅ Fetch patient info
$query = "SELECT full_name, email, phone, address, d_o_b FROM patients WHERE id = ?";
$stmt = $conn->prepare($query);
if (! $stmt) {
  error_log("Prepare failed (patient fetch): " . $conn->error);
  header('Location: ../../frontend/login.html');
  exit;
}
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$patient) {
  // invalid session user — force logout/redirect
  header("Location: ../login.html");
  exit;
}

// ✅ Handle profile update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['updateProfile'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $dob = trim($_POST['d_o_b'] ?? '');

  if ($name === '' || $email === '') {
    $message = "Name and email are required.";
  } else {
    // Build list of changes for email
    $changes = [];
    if (($patient['full_name'] ?? '') !== $name) $changes['Full Name'] = $name;
    if (($patient['email'] ?? '') !== $email) $changes['Email'] = $email;
    if (($patient['phone'] ?? '') !== $phone) $changes['Phone'] = $phone;
    if (($patient['address'] ?? '') !== $address) $changes['Address'] = $address;
    // compare date normalized
    $oldDob = $patient['d_o_b'] ?? null;
    if ($oldDob === null) $oldDob = '';
    if ($oldDob !== $dob) $changes['Date of Birth'] = $dob === '' ? null : $dob;

    // Prepare query depending on whether dob provided
    if ($dob !== '') {
      // validate date format YYYY-MM-DD
      $dObj = DateTime::createFromFormat('Y-m-d', $dob);
      if (! $dObj || $dObj->format('Y-m-d') !== $dob) {
        $message = "Invalid date format. Use YYYY-MM-DD.";
      } else {
        $update = $conn->prepare("UPDATE patients SET full_name=?, email=?, phone=?, address=?, d_o_b=? WHERE id=?");
        if ($update) {
          $update->bind_param("sssssi", $name, $email, $phone, $address, $dob, $patient_id);
        } else {
          $message = "Database error.";
          error_log("Prepare failed (profile update): " . $conn->error);
        }
      }
    } else {
      // set d_o_b to NULL when empty
      $update = $conn->prepare("UPDATE patients SET full_name=?, email=?, phone=?, address=?, d_o_b=NULL WHERE id=?");
      if ($update) {
        $update->bind_param("ssssi", $name, $email, $phone, $address, $patient_id);
      } else {
        $message = "Database error.";
        error_log("Prepare failed (profile update): " . $conn->error);
      }
    }

    if (isset($update) && $update) {
      if ($update->execute()) {
        $message = "Profile updated successfully!";

        // reload patient data to reflect changes
        $rstmt = $conn->prepare("SELECT full_name, email, phone, address, d_o_b FROM patients WHERE id = ?");
        if ($rstmt) {
          $rstmt->bind_param("i", $patient_id);
          $rstmt->execute();
          $rres = $rstmt->get_result();
          $patient = $rres ? $rres->fetch_assoc() : $patient;
          $rstmt->close();
        }

        // send notification email (use new email)
        require_once '../../backend/email_helper.php';
        $to = $email;
        $subject = "Your HMS profile was updated";
        $body  = "Hello " . htmlspecialchars($patient['full_name'] ?? $name) . ",\n\n";
        $body .= "Your profile was updated on " . date('Y-m-d H:i:s') . ".\n\n";
        if (!empty($changes)) {
          $body .= "Changed fields:\n";
          foreach ($changes as $k => $v) {
            $body .= "- " . $k . ": " . ($v === null ? '(cleared)' : $v) . "\n";
          }
        } else {
          $body .= "No visible field changes were detected, but the update action completed successfully.\n";
        }
        $body .= "\nIf you did not make this change, please contact the hospital immediately.\n\nRegards,\nHospital Management System";

        if (!sendEmail($to, $subject, $body)) {
          error_log("Failed to send patient update email to {$to}");
        }

      } else {
        $message = "Error updating profile.";
        error_log("Profile update failed: " . $update->error);
      }
      $update->close();
    }
  }
}

// ✅ Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['changePassword'])) {
  $current = $_POST['currentPassword'] ?? '';
  $new = $_POST['newPassword'] ?? '';
  $confirm = $_POST['confirmPassword'] ?? '';

  if ($new !== $confirm) {
    $message = "❌ New passwords do not match!";
  } else {
    $check = $conn->prepare("SELECT password FROM patients WHERE id=?");
    if (! $check) {
      $message = "Database error.";
      error_log("Prepare failed (password check): " . $conn->error);
    } else {
      $check->bind_param("i", $patient_id);
      $check->execute();
      $cres = $check->get_result();
      $row = $cres ? $cres->fetch_assoc() : null;
      $check->close();

      $stored = $row['password'] ?? '';
      $current_ok = false;
      if ($stored !== '') {
        if (password_verify($current, $stored)) {
          $current_ok = true;
        } elseif (md5($current) === $stored) {
          // legacy MD5 support
          $current_ok = true;
        }
      }

      if (! $current_ok) {
        $message = "❌ Incorrect current password!";
      } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $updatePass = $conn->prepare("UPDATE patients SET password=? WHERE id=?");
        if ($updatePass) {
          $updatePass->bind_param("si", $hashed, $patient_id);
          if ($updatePass->execute()) {
            $message = "✅ Password updated successfully!";
          } else {
            $message = "Error updating password.";
            error_log("Password update failed: " . $updatePass->error);
          }
          $updatePass->close();
        } else {
          $message = "Database error.";
          error_log("Prepare failed (password update): " . $conn->error);
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patient Profile - HMS</title>
  <link rel="stylesheet" href="css/patient-profile.css" />
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2>Patient Panel</h2>
      <ul>
        <li><a href="patient-dashboard.php">Dashboard</a></li>
        <li><a href="patient-appointments.php">Appointments</a></li>
        <li><a href="patient-reports.php">Reports</a></li>
        <li><a href="patient-profile.php" class="active">Profile</a></li>
        <li class="logout"><a href="../../backend/logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <header class="topbar">
        <h1>My Profile</h1>
      </header>

      <?php if (isset($message)): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Profile Info -->
      <section class="profile-info">
        <h2>Edit Personal Information</h2>
        <form method="POST">
          <div class="form-grid">
            <div class="form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($patient['full_name'] ?? '') ?>" />
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($patient['email'] ?? '') ?>" />
            </div>

            <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="d_o_b" value="<?= htmlspecialchars($patient['d_o_b'] ?? '') ?>">
          </div>

            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>"/>
            </div>

            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?= htmlspecialchars($patient['address'] ?? '') ?>" />
            </div>

            
          </div>
          <button type="submit" name="updateProfile">Save Changes</button>
        </form>
      </section>

      <!-- Change Password -->
      <section class="change-password">
        <h2>Change Password</h2>
        <form method="POST">
          <div class="form-group">
            <label for="currentPassword">Current Password</label>
            <input type="password" id="currentPassword" name="currentPassword" required />
          </div>

          <div class="form-group">
            <label for="newPassword">New Password</label>
            <input type="password" id="newPassword" name="newPassword" required />
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirm New Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" required />
          </div>

          <button type="submit" name="changePassword">Update Password</button>
        </form>
      </section>
    </main>
  </div>
</body>
</html>

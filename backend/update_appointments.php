<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include 'db_connect.php';
require_once 'email_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'error' => 'Invalid request method']);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$patientEmail = trim($_POST['email'] ?? '');
$patientName = trim($_POST['patient'] ?? '');
$appointmentDate = trim($_POST['date'] ?? '');
$appointmentTime = trim($_POST['time'] ?? '');
$doctorName = trim($_POST['doctor'] ?? '');

if ($id <= 0 || $status === '' || $patientEmail === '') {
  echo json_encode(['success' => false, 'error' => 'Missing parameters']);
  exit;
}

// 1️⃣ Update the appointment status
$stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => $conn->error]);
  exit;
}
$stmt->bind_param("si", $status, $id);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
  echo json_encode(['success' => false, 'error' => 'Failed to update status']);
  exit;
}

// 2️⃣ Send email notification
$subject = "Your Appointment Update - HMS";
$message = "
Dear $patientName,

Your appointment with Dr. $doctorName on $appointmentDate at $appointmentTime has been updated.

New Status: $status

Please log in to your Hospital Management System account to view details.

Best regards,
HMS Team
";

$emailSent = sendEmail($patientEmail, $subject, $message);

// Return response
echo json_encode([
  'success' => true,
  'emailSent' => $emailSent,
  'message' => 'Status updated and email notification sent successfully.'
]);
?>

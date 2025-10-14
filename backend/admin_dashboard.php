<?php
include 'db_connect.php';

// Fetch dashboard data
$patients = $conn->query("SELECT COUNT(*) AS total FROM patients")->fetch_assoc()['total'] ?? 0;
$doctors = $conn->query("SELECT COUNT(*) AS total FROM doctors")->fetch_assoc()['total'] ?? 0;
$appointments = $conn->query("SELECT COUNT(*) AS total FROM appointments")->fetch_assoc()['total'] ?? 0;

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
  'patients' => $patients,
  'doctors' => $doctors,
  'appointments' => $appointments
]);
?>

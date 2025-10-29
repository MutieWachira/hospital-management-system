<?php
require_once "db_connect.php";

header('Content-Type: application/json; charset=utf-8');

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
if ($doctor_id <= 0) {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare("
  SELECT id,
         patient_name,
         appointment_date AS date,
         appointment_time AS time,
         description AS notes,
         status
  FROM appointments
  WHERE doctor_id = ?
  ORDER BY appointment_date DESC, appointment_time DESC
");
if (!$stmt) {
  echo json_encode([]);
  exit;
}

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
  // normalize values if needed
  $appointments[] = $row;
}

$stmt->close();
echo json_encode($appointments);
?>

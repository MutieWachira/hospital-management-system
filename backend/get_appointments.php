<?php
require_once "db_connect.php"; // connect to your database

$doctor_id = $_GET['doctor_id'] ?? 0;
$query = $conn->prepare("SELECT id, patient_name, date, time, status, notes FROM appointments WHERE doctor_id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$result = $query->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
  $appointments[] = $row;
}

echo json_encode($appointments);
?>

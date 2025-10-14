<?php
include 'db_connect.php';

$result = $conn->query("SELECT * FROM users WHERE role = 'patient' ORDER BY id DESC");

$patients = [];
while ($row = $result->fetch_assoc()) {
  $patients[] = $row;
}

echo json_encode($patients);
?>
<?php
require_once "db_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];
$status = $data['status'];

$query = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$query->bind_param("si", $status, $id);
if ($query->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false]);
}
?>
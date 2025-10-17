<?php
include '../../backend/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get patient ID from URL
$id = $_GET['id'] ?? null;

if (!$id) {
    die("Error: No doctor ID provided.");
}

// Prepare and execute delete query
$stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>
        alert('Doctor deleted successfully!');
        window.location.href='doctor.php';
    </script>";
} else {
    echo "<p>Error deleting doctor: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>

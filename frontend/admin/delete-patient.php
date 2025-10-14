<?php
include '../../backend/db_connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get patient ID from URL
$id = $_GET['id'] ?? null;

if (!$id) {
    die("Error: No patient ID provided.");
}

// Prepare and execute delete query
$stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>
        alert('Patient deleted successfully!');
        window.location.href='patients.php';
    </script>";
} else {
    echo "<p>Error deleting patient: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>

<?php
// db_connect.php
$servername = "localhost";
$username = "root"; // default for local
$password = ""; // your MySQL password (leave empty if none)
$dbname = "hms_db"; // create this in MySQL Workbench

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>

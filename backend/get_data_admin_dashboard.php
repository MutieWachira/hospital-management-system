<?php
session_start();
include 'config.php';

// check session role
if ($_SESSION['role'] != 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Initialize response array
$response = [];

// Count patients
$queryPatients = "SELECT COUNT(*) AS total_patients FROM users WHERE role='patient'";
$patientsResult = $conn->query($queryPatients);
$response['total_patients'] = $patientsResult->fetch_assoc()['total_patients'];

// Count doctors
$queryDoctors = "SELECT COUNT(*) AS total_doctors FROM users WHERE role='doctor'";
$doctorsResult = $conn->query($queryDoctors);
$response['total_doctors'] = $doctorsResult->fetch_assoc()['total_doctors'];

// Count nurses
$queryNurses = "SELECT COUNT(*) AS total_nurses FROM users WHERE role='nurse'";
$nursesResult = $conn->query($queryNurses);
$response['total_nurses'] = $nursesResult->fetch_assoc()['total_nurses'];

// Count today's appointments
$queryAppointments = "SELECT COUNT(*) AS appointments_today 
                      FROM appointments 
                      WHERE appointment_date = CURDATE()";
$appResult = $conn->query($queryAppointments);
$response['appointments_today'] = $appResult->fetch_assoc()['appointments_today'];

// Return JSON
header('Content-Type: application/json');
echo json_encode($response);
?>

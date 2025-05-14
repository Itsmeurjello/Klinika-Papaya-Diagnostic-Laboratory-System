<?php
include 'db.php';
header('Content-Type: application/json');

$sample_id = $conn->real_escape_string($_POST['sample_id']);
$doctor_id = $conn->real_escape_string($_POST['doctor_id']);

// Update the request with assigned doctor
$sql = "UPDATE request_list SET assigned_doctor = '$doctor_id' WHERE sample_id = '$sample_id'";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
<?php
include 'db.php';
header('Content-Type: application/json');

// Enable error reporting (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Required fields
    $required = ['patientId', 'sampleId', 'patientName', 'stationWard', 'gender', 'age', 'birthDate', 'requestDate'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Assign inputs securely
    $patientId = $_POST['patientId'];
    $sampleId = $_POST['sampleId'];
    $patientName = $_POST['patientName'];
    $stationWard = $_POST['stationWard'];
    $gender = $_POST['gender'];
    $age = (int)$_POST['age'];
    $birthDate = $_POST['birthDate'];
    $requestDate = $_POST['requestDate'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO request_list 
        (patient_id, sample_id, patient_name, station_ward, gender, age, birth_date, request_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssssiss", 
        $patientId,
        $sampleId,
        $patientName,
        $stationWard,
        $gender,
        $age,
        $birthDate,
        $requestDate
    );

    if (!$stmt->execute()) {
        // Check for foreign key constraint failure
        if ($stmt->errno == 1452) {
            throw new Exception("Invalid patient ID. Make sure the patient exists in the patients table.");
        }
        throw new Exception("Database error: " . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Request added successfully']);
    $stmt->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

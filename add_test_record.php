<?php
session_start();
require_once 'config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Validate required fields
$requiredFields = ['patient_id', 'test_name', 'sample_id', 'section', 'test_date', 'status'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    die(json_encode([
        'success' => false, 
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]));
}

try {
    // Get patient information
    $patientStmt = $connect->prepare("SELECT full_name FROM patients WHERE patient_id = :patient_id");
    $patientStmt->execute([':patient_id' => $_POST['patient_id']]);
    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        die(json_encode(['success' => false, 'message' => 'Patient not found']));
    }
    
    // Check if sample ID already exists
    $checkStmt = $connect->prepare("SELECT COUNT(*) FROM test_records WHERE sample_id = :sample_id");
    $checkStmt->execute([':sample_id' => $_POST['sample_id']]);
    
    if ($checkStmt->fetchColumn() > 0) {
        die(json_encode(['success' => false, 'message' => 'Sample ID already exists']));
    }
    
    // Insert new test record
    $sql = "INSERT INTO test_records (
                patient_id, 
                patient_name, 
                test_name, 
                sample_id, 
                section, 
                test_date, 
                result, 
                status, 
                remarks, 
                performed_by
            ) VALUES (
                :patient_id,
                :patient_name,
                :test_name,
                :sample_id,
                :section,
                :test_date,
                :result,
                :status,
                :remarks,
                :performed_by
            )";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':patient_id' => $_POST['patient_id'],
        ':patient_name' => $patient['full_name'],
        ':test_name' => $_POST['test_name'],
        ':sample_id' => $_POST['sample_id'],
        ':section' => $_POST['section'],
        ':test_date' => $_POST['test_date'],
        ':result' => $_POST['result'] ?? null,
        ':status' => $_POST['status'] ?? 'Pending',
        ':remarks' => $_POST['remarks'] ?? null,
        ':performed_by' => $_SESSION['username']
    ]);
    
    $newId = $connect->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Test record added successfully',
        'id' => $newId
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
<?php
session_start();
require_once 'config/db_connect.php';

header('Content-Type: application/json');

try {
    // Required fields
    if (!isset($_POST['patient_id'])) {
        throw new Exception('Patient ID is required');
    }

    $patientId = $_POST['patient_id'];
    $sampleId = $_POST['sample_id'] ?? null;

    // Start transaction
    $connect->beginTransaction();
    
    // 1. First check if this request is already approved
    $checkQuery = "SELECT COUNT(*) FROM request_list 
                  WHERE patient_id = ?";
    $checkParams = [$patientId];
    
    if ($sampleId) {
        $checkQuery .= " AND sample_id = ?";
        $checkParams[] = $sampleId;
    }
    
    $checkStmt = $connect->prepare($checkQuery);
    $checkStmt->execute($checkParams);
    
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception('This request is already approved');
    }

    // 2. Update status in pending_requests
    $updateQuery = "UPDATE pending_requests 
                   SET status = 'Approved'
                   WHERE patient_id = ?";
    
    $updateParams = [$patientId];
    
    if ($sampleId) {
        $updateQuery .= " AND sample_id = ?";
        $updateParams[] = $sampleId;
    }
    
    $updateStmt = $connect->prepare($updateQuery);
    $updateStmt->execute($updateParams);
    
    if ($updateStmt->rowCount() === 0) {
        throw new Exception('No matching pending request found');
    }
    
    // 3. Insert into request_list (only if not already exists)
    $insertQuery = "INSERT INTO request_list 
                   (patient_id, sample_id, patient_name, station_ward, 
                    gender, age, birth_date, test_name, request_date, status)
                   SELECT 
                   patient_id, sample_id, full_name, station, 
                   gender, age, birth_date, test_name, date, 'Approved'
                   FROM pending_requests 
                   WHERE patient_id = ?";
    
    $insertParams = [$patientId];
    
    if ($sampleId) {
        $insertQuery .= " AND sample_id = ?";
        $insertParams[] = $sampleId;
    }
    
    // Add NOT EXISTS check to prevent duplicates
    $insertQuery .= " AND NOT EXISTS (
        SELECT 1 FROM request_list 
        WHERE patient_id = ?";
    
    $insertParams[] = $patientId;
    
    if ($sampleId) {
        $insertQuery .= " AND sample_id = ?";
        $insertParams[] = $sampleId;
    }
    
    $insertQuery .= ")";
    
    $insertStmt = $connect->prepare($insertQuery);
    $insertStmt->execute($insertParams);
    
    $connect->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($connect) && $connect->inTransaction()) {
        $connect->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
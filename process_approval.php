<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

// Validate patient_id
if (!isset($_POST['patient_id']) || empty($_POST['patient_id'])) {
    error_log("Missing patient_id in approval request");
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit;
}

$patientId = $_POST['patient_id'];

require_once 'config/db_connect.php';

try {
    $connect->beginTransaction();

    // 1. Get the pending request by patient_id
    $stmt = $connect->prepare("
        SELECT pr.*, p.gender, p.age, p.birth_date, p.full_name as patient_name
        FROM pending_requests pr
        LEFT JOIN patients p ON pr.patient_id = p.patient_id
        WHERE pr.patient_id = :patient_id AND pr.status = 'Pending'
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bindParam(':patient_id', $patientId);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("No pending request found for patient ID: $patientId");
    }

    // 2. Insert into approved requests
    $insertStmt = $connect->prepare("
        INSERT INTO request_list (
            patient_id, sample_id, patient_name, 
            station_ward, gender, age, birth_date, 
            request_date, test_name, clinical_info, 
            physician, status, created_at
        ) VALUES (
            :patient_id, :sample_id, :patient_name, 
            :station_ward, :gender, :age, :birth_date, 
            :request_date, :test_name, :clinical_info, 
            :physician, 'Approved', NOW()
        )
    ");
    
    $insertData = [
        ':patient_id' => $patientId,
        ':sample_id' => $request['sample_id'] ?? null,
        ':patient_name' => $request['full_name'],
        ':station_ward' => $request['station'],
        ':gender' => $request['gender'],
        ':age' => $request['age'],
        ':birth_date' => $request['birth_date'],
        ':request_date' => $request['date'] ?? date('Y-m-d H:i:s'),
        ':test_name' => $request['test_name'],
        ':clinical_info' => $request['clinical_info'] ?? '',
        ':physician' => $request['physician'] ?? ''
    ];
    
    if (!$insertStmt->execute($insertData)) {
        throw new Exception("Failed to insert into request_list");
    }

    // 3. Update pending request status
    $updateStmt = $connect->prepare("
        UPDATE pending_requests 
        SET status = 'Approved', 
            processed_at = NOW(),
            processed_by = :user_id
        WHERE patient_id = :patient_id
        AND status = 'Pending'
    ");
    $updateStmt->bindParam(':patient_id', $patientId);
    $updateStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update pending request");
    }

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Request approved successfully',
        'data' => [
            'patient_id' => $patientId,
            'patient_name' => $request['full_name'],
            'sample_id' => $request['sample_id']
        ]
    ]);

} catch (PDOException $e) {
    $connect->rollBack();
    error_log("Database Error for patient $patientId: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    $connect->rollBack();
    error_log("Approval Error for patient $patientId: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
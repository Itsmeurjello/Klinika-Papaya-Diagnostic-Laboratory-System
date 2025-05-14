<?php
session_start();
require_once 'config/db_connect.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['username'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}

// Validate required fields
if (empty($_POST['patient_id'])) {
    die(json_encode(['success' => false, 'message' => 'Missing patient ID']));
}

// Get current user ID
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? $username; // Fallback to username if user_id not set

try {
    // Start transaction
    $connect->beginTransaction();
    
    $patient_id = $_POST['patient_id'];
    $sample_id = $_POST['sample_id'] ?? null;
    $action = $_POST['action'] ?? 'approve'; // Default to approve if not specified
    
    // Build where clause
    $whereClause = "patient_id = :patient_id";
    $params = [':patient_id' => $patient_id];
    
    if ($sample_id) {
        $whereClause .= " AND sample_id = :sample_id";
        $params[':sample_id'] = $sample_id;
    }
    
    // Verify request exists and is pending
    $checkStmt = $connect->prepare("
        SELECT * FROM pending_requests 
        WHERE {$whereClause} AND status = 'Pending'
        LIMIT 1
    ");
    $checkStmt->execute($params);
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('No pending request found matching these criteria');
    }
    
    // Process based on action
    if ($action === 'approve') {
        // Update request status to Approved
        $updateStmt = $connect->prepare("
            UPDATE pending_requests 
            SET status = 'Approved',
                processed_at = NOW(),
                processed_by = :user_id
            WHERE {$whereClause}
        ");
        
        $updateParams = $params;
        $updateParams[':user_id'] = $user_id;
        
        $updateStmt->execute($updateParams);
        
        // Also insert into request_list for compatibility with existing code
        // This is to maintain backward compatibility with any code that might be using the request_list table
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
            ON DUPLICATE KEY UPDATE
                status = 'Approved',
                updated_at = NOW()
        ");
        
        $insertParams = [
            ':patient_id' => $request['patient_id'],
            ':sample_id' => $request['sample_id'],
            ':patient_name' => $request['full_name'],
            ':station_ward' => $request['station'] ?? $request['station_ward'] ?? '',
            ':gender' => $request['gender'],
            ':age' => $request['age'],
            ':birth_date' => $request['birth_date'],
            ':request_date' => $request['date'],
            ':test_name' => $request['test_name'],
            ':clinical_info' => $request['clinical_info'] ?? '',
            ':physician' => $request['physician'] ?? ''
        ];
        
        $insertStmt->execute($insertParams);
        
        $message = 'Request approved successfully';
    } 
    else if ($action === 'reject') {
        // Validate rejection reason
        if (empty($_POST['reject_reason'])) {
            throw new Exception('Rejection reason is required');
        }
        
        $reject_reason = trim($_POST['reject_reason']);
        
        // Update request status to Rejected
        $updateStmt = $connect->prepare("
            UPDATE pending_requests 
            SET status = 'Rejected',
                processed_at = NOW(),
                processed_by = :user_id,
                reject_reason = :reject_reason
            WHERE {$whereClause}
        ");
        
        $updateParams = $params;
        $updateParams[':user_id'] = $user_id;
        $updateParams[':reject_reason'] = $reject_reason;
        
        $updateStmt->execute($updateParams);
        
        // Optionally, also log to rejected_requests table for reporting
        $logStmt = $connect->prepare("
            INSERT INTO rejected_requests (
                request_id, patient_id, sample_id, rejection_reason, 
                rejected_by, rejected_at
            ) VALUES (
                :request_id, :patient_id, :sample_id, :rejection_reason,
                :rejected_by, NOW()
            )
        ");
        
        // Only execute if the rejected_requests table exists
        try {
            $logParams = [
                ':request_id' => $request['id'],
                ':patient_id' => $request['patient_id'],
                ':sample_id' => $request['sample_id'],
                ':rejection_reason' => $reject_reason,
                ':rejected_by' => $user_id
            ];
            
            $logStmt->execute($logParams);
        } catch (PDOException $e) {
            // Table might not exist, just continue
        }
        
        $message = 'Request rejected successfully';
    }
    else {
        throw new Exception('Invalid action specified');
    }
    
    $connect->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'patient_id' => $patient_id,
            'sample_id' => $sample_id,
            'status' => $action === 'approve' ? 'Approved' : 'Rejected'
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($connect) && $connect->inTransaction()) {
        $connect->rollBack();
    }
    
    error_log("Process Request Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
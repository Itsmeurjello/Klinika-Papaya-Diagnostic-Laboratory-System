<?php
session_start();
header('Content-Type: application/json');

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

// Validate request ID
if (!isset($_POST['request_id']) || !is_numeric($_POST['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

$requestId = (int)$_POST['request_id'];
$patientId = $_POST['patient_id'] ?? '';

require_once 'config/db_connect.php';

try {
    $connect->beginTransaction();

    // Update status to Rejected
    $stmt = $connect->prepare("
        UPDATE pending_requests 
        SET status = 'Rejected', 
            processed_at = NOW(),
            processed_by = :user_id
        WHERE id = :request_id
        AND status = 'Pending'
    ");
    $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update request status");
    }

    // Optionally: Insert into a rejected_requests table if you have one
    // [Add similar code to your approve process if needed]

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Request rejected successfully',
        'patient_id' => $patientId
    ]);

} catch (PDOException $e) {
    $connect->rollBack();
    error_log("Rejection Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    $connect->rollBack();
    error_log("Rejection Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
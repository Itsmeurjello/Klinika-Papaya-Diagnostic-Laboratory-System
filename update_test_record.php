<?php
session_start();
require_once 'config/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Validate required fields
if (empty($_POST['id'])) {
    die(json_encode(['success' => false, 'message' => 'Test ID is required']));
}

try {
    $testId = $_POST['id'];
    $testDate = $_POST['test_date'] ?? null;
    $status = $_POST['status'] ?? null;
    $result = $_POST['result'] ?? null;
    $remarks = $_POST['remarks'] ?? null;
    $performedBy = $_SESSION['username'];
    
    // Build update query with available fields
    $updateFields = [];
    $params = [':id' => $testId];
    
    if (!empty($testDate)) {
        $updateFields[] = "test_date = :test_date";
        $params[':test_date'] = $testDate;
    }
    
    if (!empty($status)) {
        $updateFields[] = "status = :status";
        $params[':status'] = $status;
    }
    
    if (isset($result)) { // Allow empty result (clearing)
        $updateFields[] = "result = :result";
        $params[':result'] = $result;
    }
    
    if (isset($remarks)) { // Allow empty remarks (clearing)
        $updateFields[] = "remarks = :remarks";
        $params[':remarks'] = $remarks;
    }
    
    $updateFields[] = "performed_by = :performed_by";
    $params[':performed_by'] = $performedBy;
    
    $updateFields[] = "updated_at = NOW()";
    
    if (empty($updateFields)) {
        die(json_encode(['success' => false, 'message' => 'No fields to update']));
    }
    
    $sql = "UPDATE test_records SET " . implode(", ", $updateFields) . " WHERE id = :id";
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Test record updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes were made or record not found'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
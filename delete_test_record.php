<?php
session_start();
require_once 'config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (empty($_POST['id'])) {
    die(json_encode(['success' => false, 'message' => 'Test ID is required']));
}

try {
    $testId = $_POST['id'];
    
    $checkStmt = $connect->prepare("SELECT * FROM test_records WHERE id = :id");
    $checkStmt->execute([':id' => $testId]);
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        die(json_encode(['success' => false, 'message' => 'Record not found']));
    }
    
    $stmt = $connect->prepare("DELETE FROM test_records WHERE id = :id");
    $stmt->execute([':id' => $testId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test record deleted successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
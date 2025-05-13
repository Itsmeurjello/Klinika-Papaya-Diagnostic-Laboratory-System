<?php
session_start();
require_once 'config/db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$requestId = $data['request_id'];
$reason = trim($data['reject_reason']);

if (!$requestId || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $stmt = $connect->prepare("UPDATE pending_requests SET status = 'Rejected', reject_reason = :reason WHERE request_id = :id");
    $stmt->execute([':reason' => $reason, ':id' => $requestId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
?>

<?php
require_once 'config/db_connect.php';
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM request_list WHERE status = 'Pending'");
$stmt->execute();
$result = $stmt->get_result();
echo json_encode($result->fetch_assoc());
?>
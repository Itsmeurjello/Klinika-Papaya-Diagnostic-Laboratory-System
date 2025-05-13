<?php
require_once 'config.php'; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    $stmt = $pdo->prepare("UPDATE pending_requests SET status = 'approved' WHERE id = ?");
    if ($stmt->execute([$request_id])) {
        header("Location: pendinglist.php?msg=approved");
        exit();
    } else {
        echo "Failed to approve request.";
    }
} else {
    echo "Invalid request.";
}
?>

<?php
session_start();
require_once 'config/db_connect.php'; // adjust path if needed

// CSRF protection
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token");
}

if (isset($_GET['id'])) {
    $requestId = $_GET['id'];

    try {
        $connect->beginTransaction();

        // 1. Get the approved request data
        $stmt = $connect->prepare("SELECT * FROM request_list WHERE id = :id");
        $stmt->execute(['id' => $requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            // 2. Insert into pending_requests
            $insert = $connect->prepare("INSERT INTO pending_requests (patient_id, service_type, request_date, status) VALUES (:patient_id, :service_type, :request_date, :status)");
            $insert->execute([
                'patient_id' => $request['patient_id'],
                // 'service_type' => $request['service_type'],
                'request_date' => $request['request_date'],
                'status' => 'Pending'
            ]);

            // 3. Delete from request_list
            $delete = $conn->prepare("DELETE FROM request_list WHERE id = :id");
            $delete->execute(['id' => $requestId]);

            $conn->commit();
            header("Location: requestlist.php?moved_to_pending=1");
            exit;
        } else {
            throw new Exception("Request not found.");
        }

    } catch (Exception $e) {
        $connect->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No ID provided.";
}
?>

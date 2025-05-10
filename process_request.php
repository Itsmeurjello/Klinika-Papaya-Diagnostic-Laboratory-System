<?php
session_start();
require_once 'config/db_connect.php';

header('Content-Type: application/json');

// Validate session
if (!isset($_SESSION['username'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}

// Validate required fields
$required = ['patient_id', 'test_name', 'station', 'sample_id'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die(json_encode(['success' => false, 'message' => "Missing required field: $field"]));
    }
}

// Sanitize inputs
$patient_id = (int)$_POST['patient_id'];
$test_name = htmlspecialchars($_POST['test_name']);
$station = htmlspecialchars($_POST['station']);
$sample_id = htmlspecialchars($_POST['sample_id']);
$allowed_tests = ['CBC', 'Urinalysis', 'Blood Chemistry'];

if (!in_array($test_name, $allowed_tests)) {
    die(json_encode(['success' => false, 'message' => 'Invalid test selection']));
}

// Validate sample ID format
if (!preg_match('/^LAB-\d{6,}$/', $sample_id)) {
    die(json_encode(['success' => false, 'message' => 'Invalid Sample ID format']));
}

try {
    $connect->beginTransaction();

    // Check if sample ID already exists
    $checkStmt = $connect->prepare("SELECT COUNT(*) FROM pending_requests WHERE sample_id = :sample_id");
    $checkStmt->execute([':sample_id' => $sample_id]);
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("Sample ID already exists in the system");
    }

    $stmt = $connect->prepare("
        INSERT INTO pending_requests (
            date, patient_id, full_name, station, 
            gender, age, birth_date, test_name, 
            clinical_info, physician, status, requested_by,
            sample_id
        ) 
        SELECT 
            NOW(), 
            p.patient_id, 
            p.full_name, 
            :station,
            p.gender, 
            p.age, 
            p.birth_date, 
            :test_name,
            :clinical_info,
            :physician,
            'Pending',
            :requested_by,
            :sample_id
        FROM patients p
        WHERE p.patient_id = :patient_id
    ");

    $stmt->execute([
        ':patient_id' => $patient_id,
        ':station' => $station,
        ':test_name' => $test_name,
        ':clinical_info' => $_POST['clinical_info'] ?? null,
        ':physician' => $_POST['physician'] ?? null,
        ':requested_by' => $_SESSION['username'],
        ':sample_id' => $sample_id
    ]);

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Request submitted successfully!',
        'request_id' => $connect->lastInsertId(),
        'sample_id' => $sample_id
    ]);

} catch (PDOException $e) {
    $connect->rollBack();
    error_log("Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    $connect->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
<?php
session_start();
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $patient_id = $_POST['patient_id'];
        $patient_name = $_POST['patient_name'];
        $sample_id = $_POST['sample_id'];
        $station_ward = $_POST['station_ward'];
        $gender = $_POST['gender'];
        $age = $_POST['age'];
        $birth_date = $_POST['birth_date'];
        $tests = $_POST['tests'];
        $status = 'Pending';
        $request_date = date('Y-m-d H:i:s'); 
        
        $stmt = $connect->prepare("INSERT INTO pending_requests 
                                 (patient_id, patient_name, sample_id, station, 
                                  gender, age, birth_date, test_name, status, date) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $patient_id,
            $patient_name,
            $sample_id,
            $station_ward,
            $gender,
            $age,
            $birth_date,
            $tests,
            $status,
            $request_date
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Request moved to pending successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
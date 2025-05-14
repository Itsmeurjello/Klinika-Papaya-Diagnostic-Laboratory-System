<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the patient_id is set
    if (isset($_POST['patient_id']) && !empty($_POST['patient_id'])) {
        $patient_id = $_POST['patient_id'];
        
        if (!is_numeric($patient_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid patient ID format'
            ]);
            exit;
        }
        
        try {
            $conn->begin_transaction();
            
            $infoStmt = $conn->prepare("SELECT full_name FROM patients WHERE patient_id = ? AND delete_status != 1");
            $infoStmt->bind_param("i", $patient_id);
            $infoStmt->execute();
            $result = $infoStmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Patient not found or already deleted");
            }
            
            $patientInfo = $result->fetch_assoc();
            $infoStmt->close();
            
            $stmt = $conn->prepare("UPDATE patients SET delete_status = 1 WHERE patient_id = ?");
            $stmt->bind_param("i", $patient_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Patient deleted successfully',
                        'patient_id' => $patient_id,
                        'patient_name' => $patientInfo['full_name']
                    ]);
                } else {
                    throw new Exception("Patient not found or already deleted");
                }
            } else {
                throw new Exception("Error deleting patient: " . $stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Patient ID is required'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. POST required.'
    ]);
}

$conn->close();
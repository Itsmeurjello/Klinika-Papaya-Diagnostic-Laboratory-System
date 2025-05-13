<?php
include 'db.php';

header('Content-Type: application/json');


function validatePatientData($data) {
    $errors = [];
    
    if (empty($data['full_name'])) {
        $errors[] = "Full name is required";
    } elseif (strlen($data['full_name']) > 100) {
        $errors[] = "Full name must be less than 100 characters";
    }
    
    if (empty($data['gender'])) {
        $errors[] = "Gender is required";
    } elseif (!in_array($data['gender'], ['Male', 'Female'])) {
        $errors[] = "Gender must be Male or Female";
    }
    
    if (!isset($data['age']) || $data['age'] === '') {
        $errors[] = "Age is required";
    } elseif (!is_numeric($data['age']) || $data['age'] < 0 || $data['age'] > 150) {
        $errors[] = "Age must be a valid number between 0 and 150";
    }
    
    if (empty($data['birth_date'])) {
        $errors[] = "Birth date is required";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birth_date'])) {
        $errors[] = "Birth date must be in YYYY-MM-DD format";
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $full_name = $_POST['full_name'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $age = isset($_POST['age']) ? (int)$_POST['age'] : '';
        $birth_date = $_POST['birth_date'] ?? '';
        
        $patientData = [
            'full_name' => $full_name,
            'gender' => $gender,
            'age' => $age,
            'birth_date' => $birth_date
        ];
        
        $errors = validatePatientData($patientData);
        
        if (count($errors) > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Validation failed', 
                'errors' => $errors
            ]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO patients (full_name, gender, age, birth_date, delete_status) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssis", $full_name, $gender, $age, $birth_date);

        if ($stmt->execute()) {
            $patient_id = $conn->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient added successfully',
                'patient' => [
                    'patient_id' => $patient_id,
                    'full_name' => $full_name,
                    'gender' => $gender,
                    'age' => $age,
                    'birth_date' => $birth_date
                ]
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. POST required.'
    ]);
}

$conn->close();
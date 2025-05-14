<?php
require_once 'config/db_connect.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS pending_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        patient_id VARCHAR(50) NOT NULL,
        sample_id VARCHAR(50) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        station VARCHAR(50) NOT NULL,
        gender VARCHAR(10) NOT NULL,
        age INT NOT NULL,
        test_name VARCHAR(100) NOT NULL,
        clinical_info TEXT,
        physician VARCHAR(100),
        status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
        requested_by VARCHAR(50),
        processed_by VARCHAR(50) DEFAULT NULL,
        processed_at DATETIME DEFAULT NULL
    )";
    
    $connect->exec($sql);
    echo "Table 'pending_requests' created successfully!";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
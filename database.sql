CREATE DATABASE mydatabase;
USE mydatabase;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Changed to hashed password storage
    role ENUM('admin','staff') DEFAULT 'staff'
);

-- Insert admin with hashed password
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

CREATE TABLE patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('MALE', 'FEMALE') NOT NULL,
    age INT NOT NULL,
    birth_date DATE NOT NULL
);

INSERT INTO patients (full_name, gender, age, birth_date) VALUES
('ORTEGA, JELLO', 'MALE', 23, '2002-01-15'),
('MADRID, FRANCE PAUL', 'MALE', 24, '2001-07-10');

CREATE TABLE pending_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    patient_id INT NOT NULL,
    sample_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    station VARCHAR(50) NOT NULL,
    station_ward VARCHAR(100) NOT NULL,
    gender ENUM('MALE','FEMALE') NOT NULL,
    age INT NOT NULL,
    birth_date DATE NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    clinical_info TEXT,
    physician VARCHAR(100),
    status ENUM('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
    requested_by VARCHAR(50) NOT NULL,
    processed_by VARCHAR(50),
    processed_at DATETIME,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);

-- Sample pending request
INSERT INTO pending_requests (
    patient_id, sample_id, full_name, station, station_ward, 
    gender, age, birth_date, test_name, requested_by
) VALUES (
    1, 'SMP-001', 'ORTEGA, JELLO', 'Lab', 'General Ward',
    'MALE', 23, '2002-01-15', 'Blood Test', 'admin'
);
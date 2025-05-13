<?php
require_once 'config/db_connect.php'; // Make sure this path is correct

// Admin user credentials
$username = 'admin';
$password = 'admin123'; // Plaintext password

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare and execute SQL to insert admin user into the database
$sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':password', $hashedPassword);
$stmt->execute();

echo "Admin user created with hashed password.";
?>

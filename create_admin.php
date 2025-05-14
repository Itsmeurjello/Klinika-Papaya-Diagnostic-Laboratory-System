<?php
require_once 'config/db_connect.php';

$username = 'admin';
$password = 'admin123';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':password', $hashedPassword);
$stmt->execute();

echo "Admin user created with hashed password.";
?>

<?php
include 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birth_date = $_POST['birth_date'];

    $sql = "UPDATE patients SET full_name = ?, gender = ?, age = ?, birth_date = ? WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ssisi", $full_name, $gender, $age, $birth_date, $patient_id);

    if ($stmt->execute()) {
        header('Location: dashboard.php');  
        exit();
    } else {
        echo "Error updating patient: " . $stmt->error; 
    }
}
?>

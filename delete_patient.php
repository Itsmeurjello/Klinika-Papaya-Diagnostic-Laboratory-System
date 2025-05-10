<?php
include 'db.php'; // Database connection

// Check if the patient_id is set
if (isset($_POST['patient_id'])) {
    $patient_id = $_POST['patient_id'];

    // Perform the deletion query
    $sql = "UPDATE patients SET delete_status = 1 WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);

    if ($stmt->execute()) {
        echo 'success'; // On success
    } else {
        echo 'error'; // On failure
    }

    $stmt->close();
} else {
    echo 'error'; // If no patient_id is sent
}
?>

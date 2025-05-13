<?php
include 'db.php';  // Include database connection

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the form data
    $patient_id = $_POST['patient_id'];
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birth_date = $_POST['birth_date'];

    // Prepare the SQL query to update the patient data
    $sql = "UPDATE patients SET full_name = ?, gender = ?, age = ?, birth_date = ? WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);

    // Bind the parameters to prevent SQL injection
    $stmt->bind_param("ssisi", $full_name, $gender, $age, $birth_date, $patient_id);

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        // Redirect to the patient list (or wherever you want)
        header('Location: dashboard.php');  // Redirect back to the dashboard
        exit();
    } else {
        echo "Error updating patient: " . $stmt->error;  // Error handling
    }
}
?>

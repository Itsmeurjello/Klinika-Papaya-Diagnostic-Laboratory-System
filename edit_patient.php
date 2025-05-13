<?php
include 'db.php';

if (isset($_POST['patient_id'])) {
    $patient_id = $_POST['patient_id'];
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birth_date = $_POST['birth_date'];

    // Update query
    $sqlUpdate = "UPDATE patients SET full_name = ?, gender = ?, age = ?, birth_date = ? WHERE patient_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('ssiss', $full_name, $gender, $age, $birth_date, $patient_id);
    
    if ($stmtUpdate->execute()) {
        // Redirect to the patient list page after update
        header("Location: patient_list.php?update_success=true");
        exit;
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>
<?php
// Display success message if redirected
if (isset($_GET['update_success']) && $_GET['update_success'] == 'true') {
    echo "<p style='color: green;'>Patient information updated successfully.</p>";
}
?>

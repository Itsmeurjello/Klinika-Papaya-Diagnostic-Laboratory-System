<?php
include 'db.php';
$sample_id = $_GET['sample_id'];
$request = $conn->query("SELECT * FROM request_list WHERE sample_id = '$sample_id'")->fetch_assoc();
?>

<div class="row">
    <div class="col-md-6">
        <p><strong>Patient ID:</strong> <?= htmlspecialchars($request['patient_id']) ?></p>
        <p><strong>Sample ID:</strong> <?= htmlspecialchars($request['sample_id']) ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($request['patient_name']) ?></p>
        <p><strong>Station/Ward:</strong> <?= htmlspecialchars($request['station_ward']) ?></p>
    </div>
    <div class="col-md-6">
        <p><strong>Gender:</strong> <?= htmlspecialchars($request['gender']) ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($request['age']) ?></p>
        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($request['birth_date']) ?></p>
        <p><strong>Request Date:</strong> <?= htmlspecialchars($request['request_date']) ?></p>
    </div>
</div>
<?php
session_start();
require_once 'config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$recordId = $_GET['id'];

try {
    // Get the test record
    $stmt = $connect->prepare("
        SELECT tr.*, p.full_name, p.gender, p.age, p.birth_date 
        FROM test_records tr
        LEFT JOIN patients p ON tr.patient_id = p.patient_id
        WHERE tr.id = :id
    ");
    $stmt->bindParam(':id', $recordId);
    $stmt->execute();
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        // Record not found
        header("Location: records.php");
        exit();
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Test Record - Klinika Papaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .record-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .record-section {
            margin-bottom: 30px;
        }
        
        .result-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding: 20px;
            }
            
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="no-print">
        <a href="logout.php" onclick="return confirm('Log out?')">Logout</a>
    </header>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Test Record Details</h2>
                <div class="no-print">
                    <button class="btn btn-secondary me-2" onclick="goBack()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <?php if ($record['status'] != 'Completed'): ?>
                    <a href="update_record.php?id=<?= $recordId ?>" class="btn btn-primary me-2">
                        <i class="bi bi-pencil"></i> Update
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
            
            <!-- Status Badge -->
            <div class="record-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Test Record #<?= htmlspecialchars($record['id']) ?></h5>
                        <p class="mb-0">
                            Status: 
                            <?php if ($record['status'] == 'Pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php elseif ($record['status'] == 'In Progress'): ?>
                                <span class="badge bg-info">In Progress</span>
                            <?php elseif ($record['status'] == 'Completed'): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0"><strong>Sample ID:</strong> <?= htmlspecialchars($record['sample_id']) ?></p>
                        <p class="mb-0"><strong>Test Date:</strong> <?= date('F d, Y h:i A', strtotime($record['test_date'])) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Patient Information -->
            <div class="record-section">
                <h4>Patient Information</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Patient ID:</strong> <?= htmlspecialchars($record['patient_id']) ?></p>
                                <p><strong>Name:</strong> <?= htmlspecialchars($record['patient_name']) ?></p>
                                <p><strong>Gender:</strong> <?= htmlspecialchars($record['gender'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Age:</strong> <?= htmlspecialchars($record['age'] ?? 'N/A') ?></p>
                                <p><strong>Birth Date:</strong> <?= $record['birth_date'] ? date('F d, Y', strtotime($record['birth_date'])) : 'N/A' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Test Information -->
            <div class="record-section">
                <h4>Test Information</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Test Name:</strong> <?= htmlspecialchars($record['test_name']) ?></p>
                                <p><strong>Section:</strong> <?= htmlspecialchars($record['section']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Performed By:</strong> <?= htmlspecialchars($record['performed_by'] ?? 'N/A') ?></p>
                                <p><strong>Created:</strong> <?= date('F d, Y h:i A', strtotime($record['created_at'])) ?></p>
                                <p><strong>Last Updated:</strong> <?= date('F d, Y h:i A', strtotime($record['updated_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Test Results -->
            <div class="record-section">
                <h4>Test Results</h4>
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($record['result'])): ?>
                            <div class="result-container">
                                <?= nl2br(htmlspecialchars($record['result'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No results recorded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Remarks -->
            <div class="record-section">
                <h4>Remarks</h4>
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($record['remarks'])): ?>
                            <div class="result-container">
                                <?= nl2br(htmlspecialchars($record['remarks'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No remarks recorded.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer with timestamp -->
            <div class="mt-5 text-center text-muted">
                <small>Record viewed on <?= date('F d, Y h:i:s A') ?> by <?= htmlspecialchars($_SESSION['username']) ?></small>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>
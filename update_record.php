<?php
session_start();
require_once 'config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$recordId = $_GET['id'];
$errorMsg = null;
$successMsg = null;

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
    
    // Check if the record is already completed
    if ($record['status'] === 'Completed') {
        header("Location: view_record.php?id={$recordId}");
        exit();
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("CSRF token validation failed");
        }
        
        // Validate and sanitize inputs
        $testDate = !empty($_POST['test_date']) ? $_POST['test_date'] : null;
        $status = !empty($_POST['status']) ? $_POST['status'] : 'Pending';
        $result = isset($_POST['result']) ? $_POST['result'] : null;
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : null;
        
        // Validate status
        if (!in_array($status, ['Pending', 'In Progress', 'Completed'])) {
            $errorMsg = "Invalid status value.";
        }
        
        // If no errors, update the record
        if (!$errorMsg) {
            // Build update query with available fields
            $updateFields = [];
            $params = [':id' => $recordId];
            
            if (!empty($testDate)) {
                $updateFields[] = "test_date = :test_date";
                $params[':test_date'] = $testDate;
            }
            
            if (!empty($status)) {
                $updateFields[] = "status = :status";
                $params[':status'] = $status;
            }
            
            if (isset($result)) { // Allow empty result (clearing)
                $updateFields[] = "result = :result";
                $params[':result'] = $result;
            }
            
            if (isset($remarks)) { // Allow empty remarks (clearing)
                $updateFields[] = "remarks = :remarks";
                $params[':remarks'] = $remarks;
            }
            
            $updateFields[] = "performed_by = :performed_by";
            $params[':performed_by'] = $_SESSION['username'];
            
            $updateFields[] = "updated_at = NOW()";
            
            if (empty($updateFields)) {
                $errorMsg = "No fields to update.";
            } else {
                $sql = "UPDATE test_records SET " . implode(", ", $updateFields) . " WHERE id = :id";
                $updateStmt = $connect->prepare($sql);
                $updateStmt->execute($params);
                
                if ($updateStmt->rowCount() > 0) {
                    $successMsg = "Test record updated successfully.";
                    
                    // Refresh record data after update
                    $stmt->execute([':id' => $recordId]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $errorMsg = "No changes were made or record not found.";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $errorMsg = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Test Record - Klinika Papaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <a href="logout.php" onclick="return confirm('Log out?')">Logout</a>
    </header>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Update Test Record</h2>
                <button class="btn btn-secondary" onclick="goBack()">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
            </div>
            
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>
            
            <?php if ($successMsg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <!-- Patient Information (Read-only) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Patient Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient ID</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['patient_id']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Patient Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['patient_name']) ?>" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['gender'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Age</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['age'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Birth Date</label>
                                <input type="text" class="form-control" value="<?= $record['birth_date'] ? date('Y-m-d', strtotime($record['birth_date'])) : 'N/A' ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Test Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Test Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Test Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['test_name']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Section</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['section']) ?>" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Sample ID</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($record['sample_id']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Test Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="test_date" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($record['test_date'])) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" required>
                                    <option value="Pending" <?= $record['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $record['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Completed" <?= $record['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <div class="form-text">Warning: Once a test is marked as "Completed", it cannot be edited again.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Test Results -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Results</label>
                            <textarea class="form-control" name="result" rows="10"><?= htmlspecialchars($record['result'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="4"><?= htmlspecialchars($record['remarks'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="view_record.php?id=<?= $recordId ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save Changes</button>
                    <button type="button" id="completeBtn" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmCompleteModal">
                        Mark as Completed
                    </button>
                </div>
            </form>
            
            <!-- Confirm Completion Modal -->
            <div class="modal fade" id="confirmCompleteModal" tabindex="-1" aria-labelledby="confirmCompleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmCompleteModalLabel">Confirm Completion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to mark this test as completed?</p>
                            <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. Once a test is marked as completed, it cannot be edited again.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="confirmCompleteBtn" class="btn btn-success">Mark as Completed</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function goBack() {
            window.history.back();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const statusSelect = document.querySelector('select[name="status"]');
            const confirmCompleteBtn = document.getElementById('confirmCompleteBtn');
            
            // Handle the confirmation modal button click
            confirmCompleteBtn.addEventListener('click', function() {
                statusSelect.value = 'Completed';
                form.submit();
            });
            
            // Success message auto-hide after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.transition = 'opacity 1s';
                    successAlert.style.opacity = '0';
                    setTimeout(function() {
                        successAlert.remove();
                    }, 1000);
                }, 5000);
            }
            
            // Form submission confirmation
            form.addEventListener('submit', function(event) {
                // If the status is changing to "Completed" without using the modal
                if (statusSelect.value === 'Completed' && '<?= $record['status'] ?>' !== 'Completed') {
                    event.preventDefault();
                    const modal = new bootstrap.Modal(document.getElementById('confirmCompleteModal'));
                    modal.show();
                }
            });
        });
    </script>
</body>
</html>
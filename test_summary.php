<?php
session_start();
require_once 'config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get search term if provided
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

try {
    // Build the query based on search term
    $whereClause = '';
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereClause = "WHERE test_name LIKE :search OR result LIKE :search OR remarks LIKE :search";
        $params[':search'] = "%$searchTerm%";
    }
    
    // Get total count of test summaries
    $countSql = "SELECT COUNT(*) FROM test_records $whereClause";
    $countStmt = $connect->prepare($countSql);
    
    if (!empty($params)) {
        $countStmt->execute($params);
    } else {
        $countStmt->execute();
    }
    
    $totalTests = $countStmt->fetchColumn();
    $totalPages = ceil($totalTests / $recordsPerPage);
    
    // Get current page of test summaries
    $sql = "SELECT * FROM test_records $whereClause ORDER BY test_date DESC LIMIT :limit OFFSET :offset";
    $stmt = $connect->prepare($sql);
    
    // Bind parameters
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $testSummaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errMsg = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Summary</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>
    <header>
        <a href="logout.php" onclick="return confirm('Log out?')">Logout</a>
    </header>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="table-container">
            <h2>Test Summary</h2>

            <!-- Search Bar and Add Button -->
            <div class="table-controls">
                <form method="GET" class="d-flex mb-3" style="width:70%">
                    <input type="text" class="form-control" name="search" placeholder="Search by Test Name, Result or Remarks" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="test_summary.php" class="btn btn-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTestModal">
                    <i class="bi bi-plus-circle"></i> Add Test Record
                </button>
            </div>

            <!-- Record Count -->
            <div class="record-count">
                <?php if (isset($totalTests)): ?>
                    Total Records: <?php echo $totalTests; ?>
                <?php endif; ?>
            </div>

            <!-- Error Message Display -->
            <?php if(isset($errMsg)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Test Summary Table -->
            <table class="table table-bordered table-hover test-summary-table">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Test Name</th>
                        <th>Sample ID</th>
                        <th>Test Date</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($testSummaries) && count($testSummaries) > 0): ?>
                        <?php foreach ($testSummaries as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['id']); ?></td>
                                <td><?php echo htmlspecialchars($test['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['sample_id']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($test['test_date']))); ?></td>
                                <td>
                                    <?php if ($test['status'] == 'Pending'): ?>
                                        <span class="badge bg-warning text-dark status-badge">Pending</span>
                                    <?php elseif ($test['status'] == 'In Progress'): ?>
                                        <span class="badge bg-info status-badge">In Progress</span>
                                    <?php elseif ($test['status'] == 'Completed'): ?>
                                        <span class="badge bg-success status-badge">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($test['result']) ? htmlspecialchars(substr($test['result'], 0, 30)) . (strlen($test['result']) > 30 ? '...' : '') : 'N/A'; ?></td>
                                <td><?php echo !empty($test['remarks']) ? htmlspecialchars(substr($test['remarks'], 0, 30)) . (strlen($test['remarks']) > 30 ? '...' : '') : 'N/A'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm view-btn" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#viewTestModal" 
                                           data-id="<?php echo $test['id']; ?>"
                                           data-patient="<?php echo htmlspecialchars($test['patient_name']); ?>"
                                           data-test="<?php echo htmlspecialchars($test['test_name']); ?>"
                                           data-sample="<?php echo htmlspecialchars($test['sample_id']); ?>"
                                           data-date="<?php echo htmlspecialchars($test['test_date']); ?>"
                                           data-result="<?php echo htmlspecialchars($test['result'] ?? ''); ?>"
                                           data-remarks="<?php echo htmlspecialchars($test['remarks'] ?? ''); ?>"
                                           data-status="<?php echo htmlspecialchars($test['status']); ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    
                                    <?php if ($test['status'] != 'Completed'): ?>
                                    <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#editTestModal" 
                                           data-id="<?php echo $test['id']; ?>"
                                           data-patient="<?php echo htmlspecialchars($test['patient_name']); ?>"
                                           data-test="<?php echo htmlspecialchars($test['test_name']); ?>"
                                           data-sample="<?php echo htmlspecialchars($test['sample_id']); ?>"
                                           data-date="<?php echo htmlspecialchars($test['test_date']); ?>"
                                           data-result="<?php echo htmlspecialchars($test['result'] ?? ''); ?>"
                                           data-remarks="<?php echo htmlspecialchars($test['remarks'] ?? ''); ?>"
                                           data-status="<?php echo htmlspecialchars($test['status']); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                           data-id="<?php echo $test['id']; ?>"
                                           data-test="<?php echo htmlspecialchars($test['test_name']); ?>"
                                           data-patient="<?php echo htmlspecialchars($test['patient_name']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No test summaries available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Test Modal -->
    <div class="modal fade" id="viewTestModal" tabindex="-1" aria-labelledby="viewTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTestModalLabel">Test Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Patient Name:</label>
                            <p id="viewPatientName"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Test Name:</label>
                            <p id="viewTestName"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sample ID:</label>
                            <p id="viewSampleId"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Test Date:</label>
                            <p id="viewTestDate"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Status:</label>
                            <p id="viewStatus"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Test Results:</label>
                            <div id="viewResult" class="border p-3 bg-light rounded"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Remarks:</label>
                            <div id="viewRemarks" class="border p-3 bg-light rounded"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Test Modal -->
    <div class="modal fade" id="editTestModal" tabindex="-1" aria-labelledby="editTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTestModalLabel">Edit Test Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTestForm">
                        <input type="hidden" id="editTestId" name="id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient Name:</label>
                                <input type="text" class="form-control" id="editPatientName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Test Name:</label>
                                <input type="text" class="form-control" id="editTestName" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Sample ID:</label>
                                <input type="text" class="form-control" id="editSampleId" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Test Date:</label>
                                <input type="datetime-local" class="form-control" id="editTestDate" name="test_date">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Status:</label>
                                <select class="form-select" id="editStatus" name="status">
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Test Results:</label>
                                <textarea class="form-control" id="editResult" name="result" rows="4"></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Remarks:</label>
                                <textarea class="form-control" id="editRemarks" name="remarks" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Test Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Test Modal -->
    <div class="modal fade" id="addTestModal" tabindex="-1" aria-labelledby="addTestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTestModalLabel">Add New Test Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTestForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Patient ID:</label>
                                <select class="form-select" id="addPatientId" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php
                                    try {
                                        $patientStmt = $connect->query("SELECT patient_id, full_name FROM patients ORDER BY full_name");
                                        while ($patient = $patientStmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $patient['patient_id'] . '">' . 
                                                 htmlspecialchars($patient['patient_id'] . ' - ' . $patient['full_name']) . '</option>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<option value="">Error loading patients</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Test Name:</label>
                                <select class="form-select" id="addTestName" name="test_name" required>
                                    <option value="">Select Test</option>
                                    <option value="CBC">Complete Blood Count (CBC)</option>
                                    <option value="Urinalysis">Urinalysis</option>
                                    <option value="Blood Chemistry">Blood Chemistry</option>
                                    <option value="COVID-19 Test">COVID-19 Test</option>
                                    <option value="Lipid Panel">Lipid Panel</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Sample ID:</label>
                                <input type="text" class="form-control" id="addSampleId" name="sample_id" 
                                       placeholder="LAB-######" required pattern="LAB-\d{6}">
                                <small class="form-text text-muted">Format: LAB-123456</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Section:</label>
                                <select class="form-select" id="addSection" name="section" required>
                                    <option value="">Select Section</option>
                                    <option value="Hematology">Hematology</option>
                                    <option value="Chemistry">Chemistry</option>
                                    <option value="Clinical Microscopy">Clinical Microscopy</option>
                                    <option value="Serology">Serology</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Test Date:</label>
                                <input type="datetime-local" class="form-control" id="addTestDate" name="test_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status:</label>
                                <select class="form-select" id="addStatus" name="status">
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Test Results:</label>
                                <textarea class="form-control" id="addResult" name="result" rows="4" placeholder="Enter test results here"></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Remarks:</label>
                                <textarea class="form-control" id="addRemarks" name="remarks" rows="3" placeholder="Enter any additional remarks"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Add Test Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this test record?</p>
                    <p><strong>Test:</strong> <span id="deleteTestName"></span></p>
                    <p><strong>Patient:</strong> <span id="deletePatientName"></span></p>
                    <input type="hidden" id="deleteTestId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Test Details
            const viewTestModal = document.getElementById('viewTestModal');
            if (viewTestModal) {
                viewTestModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    document.getElementById('viewPatientName').textContent = button.getAttribute('data-patient');
                    document.getElementById('viewTestName').textContent = button.getAttribute('data-test');
                    document.getElementById('viewSampleId').textContent = button.getAttribute('data-sample');
                    
                    const testDate = new Date(button.getAttribute('data-date'));
                    document.getElementById('viewTestDate').textContent = testDate.toLocaleString();
                    
                    const status = button.getAttribute('data-status');
                    let statusHtml = '';
                    if (status === 'Pending') {
                        statusHtml = '<span class="badge bg-warning text-dark">Pending</span>';
                    } else if (status === 'In Progress') {
                        statusHtml = '<span class="badge bg-info">In Progress</span>';
                    } else if (status === 'Completed') {
                        statusHtml = '<span class="badge bg-success">Completed</span>';
                    }
                    document.getElementById('viewStatus').innerHTML = statusHtml;
                    
                    document.getElementById('viewResult').innerHTML = button.getAttribute('data-result') || 'No results recorded';
                    document.getElementById('viewRemarks').innerHTML = button.getAttribute('data-remarks') || 'No remarks';
                });
            }
            
            // Edit Test Record
            const editTestModal = document.getElementById('editTestModal');
            if (editTestModal) {
                editTestModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    document.getElementById('editTestId').value = button.getAttribute('data-id');
                    document.getElementById('editPatientName').value = button.getAttribute('data-patient');
                    document.getElementById('editTestName').value = button.getAttribute('data-test');
                    document.getElementById('editSampleId').value = button.getAttribute('data-sample');
                    
                    // Format the date-time for the datetime-local input
                    const testDate = new Date(button.getAttribute('data-date'));
                    const year = testDate.getFullYear();
                    const month = String(testDate.getMonth() + 1).padStart(2, '0');
                    const day = String(testDate.getDate()).padStart(2, '0');
                    const hours = String(testDate.getHours()).padStart(2, '0');
                    const minutes = String(testDate.getMinutes()).padStart(2, '0');
                    
                    document.getElementById('editTestDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;
                    document.getElementById('editStatus').value = button.getAttribute('data-status');
                    document.getElementById('editResult').value = button.getAttribute('data-result');
                    document.getElementById('editRemarks').value = button.getAttribute('data-remarks');
                });
            }
            
            // Handle Edit Form Submission
            const editTestForm = document.getElementById('editTestForm');
            if (editTestForm) {
                editTestForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('update_test_record.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred while updating the record.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while updating the record.'
                        });
                    });
                });
            }
            
            // Handle Add Form Submission
            const addTestForm = document.getElementById('addTestForm');
            if (addTestForm) {
                addTestForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch('add_test_record.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred while adding the record.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while adding the record.'
                        });
                    });
                });
            }
            
            // Delete Test Record
            const deleteButtons = document.querySelectorAll('.delete-btn');
            if (deleteButtons.length > 0) {
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const testName = this.getAttribute('data-test');
                        const patientName = this.getAttribute('data-patient');
                        
                        document.getElementById('deleteTestId').value = id;
                        document.getElementById('deleteTestName').textContent = testName;
                        document.getElementById('deletePatientName').textContent = patientName;
                        
                        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                        deleteModal.show();
                    });
                });
            }
            
            // Confirm Delete
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function() {
                    const id = document.getElementById('deleteTestId').value;
                    
                    fetch('delete_test_record.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred while deleting the record.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the record.'
                        });
                    });
                    
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                    deleteModal.hide();
                });
            }
            
            // Auto generate sample ID in add form
            const addSampleIdField = document.getElementById('addSampleId');
            if (addSampleIdField && addSampleIdField.value === '') {
                const randomNum = Math.floor(100000 + Math.random() * 900000); // 6-digit number
                addSampleIdField.value = `LAB-${randomNum}`;
            }
            
            // Set current date-time in add form
            const addTestDateField = document.getElementById('addTestDate');
            if (addTestDateField && addTestDateField.value === '') {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                
                addTestDateField.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        });
    </script>
</body>
</html>
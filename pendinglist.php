<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

try {
    // Get total count of pending requests
    $countStmt = $connect->prepare("
    SELECT COUNT(*) 
    FROM pending_requests
    ");
    $countStmt->execute();
    $totalPending = $countStmt->fetchColumn();
    $totalPages = ceil($totalPending / $recordsPerPage);

    // Get current page of pending requests
    
    $stmt = $connect->prepare("
    SELECT pr.*, p.gender, p.age, p.birth_date
    FROM pending_requests pr
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    ORDER BY pr.date DESC
    LIMIT :limit OFFSET :offset
");
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending List - Klinika Papaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px 40px;
        }
        header {
            background-color: #f8f9fa;
            padding: 10px 30px;
            text-align: right;
            position: fixed;
            width: calc(100% - 250px);
            top: 0;
            z-index: 1000;
            left: 250px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        header a {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        .table-container {
            margin-top: 80px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 1150px;
        }
        table {
            width: 100%;
            font-size: 16px;
        }
        h2 {
            margin-bottom: 20px;
        }
        .record-count {
            margin-bottom: 20px;
            font-weight: bold;
        }
        .pending-badge {
            font-size: 0.8rem;
            vertical-align: top;
            margin-left: 5px;
        }
        .modal-lg {
            max-width: 800px;
        }
        .reject-reason-container {
        margin-top: 20px;
        display: none;
    }
    </style>
</head>
<body>
    <header>
        <a href="logout.php" onclick="return confirm('Log out?')">Logout</a>
    </header>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="table-container">
            <h2>PENDING LIST <span class="badge bg-danger pending-badge"><?= $totalPending ?></span></h2>

            <div class="record-count">Total Pending Requests: <?= $totalPending ?></div>

            <div class="mb-3">
                <input type="text" id="searchPending" class="form-control" placeholder="Search pending requests...">
            </div>
            <div class="mb-3">
                <label for="statusFilter" class="form-label">Filter by Status:</label>
                <select id="statusFilter" class="form-select w-25">
                    <option value="all">All Requests</option>
                    <option value="Pending" selected>Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <table id="pendingTable" class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Patient ID</th>
                        <th>Sample ID</th>
                        <th>Name</th>
                        <th>Station/Ward</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Test</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <?php if (!empty($pending_requests)): ?>
                        <?php foreach ($pending_requests as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['patient_id']) ?></td>
                            <td><?= htmlspecialchars($row['sample_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['station']) ?></td>
                            <td><?= htmlspecialchars($row['gender']) ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['test_name']) ?></td>
                            <td>
                                <?php if($row['status'] == 'Pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif($row['status'] == 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif($row['status'] == 'Rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                    <?php if(!empty($row['reject_reason'])): ?>
                                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="<?= htmlspecialchars($row['reject_reason']) ?>"></i>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'Pending'): ?>
                                    <button class="btn btn-sm btn-info view-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewRequestModal"
                                            data-id="<?= $row['request_id'] ?? '' ?>"
                                            data-patient-id="<?= htmlspecialchars($row['patient_id'] ?? '') ?>"
                                            data-sample-id="<?= htmlspecialchars($row['sample_id'] ?? '') ?>"
                                            data-name="<?= htmlspecialchars($row['full_name'] ?? '') ?>"
                                            data-station="<?= htmlspecialchars($row['station'] ?? '') ?>"
                                            data-gender="<?= htmlspecialchars($row['gender'] ?? '') ?>"
                                            data-age="<?= htmlspecialchars($row['age'] ?? '') ?>"
                                            data-birth-date="<?= htmlspecialchars($row['birth_date'] ?? '') ?>"
                                            data-test-name="<?= htmlspecialchars($row['test_name'] ?? '') ?>"
                                            data-clinical-info="<?= htmlspecialchars($row['clinical_info'] ?? '') ?>"
                                            data-physician="<?= htmlspecialchars($row['physician'] ?? '') ?>"
                                            data-date="<?= htmlspecialchars($row['date'] ?? '') ?>">
                                        View Request
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary view-details-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewDetailsModal"
                                            data-id="<?= $row['request_id'] ?? '' ?>"
                                            data-status="<?= htmlspecialchars($row['status'] ?? '') ?>"
                                            data-processed-by="<?= htmlspecialchars($row['processed_by'] ?? '') ?>"
                                            data-processed-at="<?= htmlspecialchars($row['processed_at'] ?? '') ?>"
                                            data-reject-reason="<?= htmlspecialchars($row['reject_reason'] ?? '') ?>">
                                        View Details
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">No pending requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>

    <!-- View Request Modal -->
    <div class="modal fade" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewRequestModalLabel">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="requestActionForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" id="requestId" name="request_id">
                        <input type="hidden" id="patientId" name="patient_id">
                        <input type="hidden" id="sampleId" name="sample_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Request Date</label>
                                <input type="text" class="form-control" id="viewDate" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Patient ID</label>
                                <input type="text" class="form-control" id="viewPatientId" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Sample ID</label>
                                <input type="text" class="form-control" id="viewSampleId" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="viewName" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Station/Ward</label>
                                <input type="text" class="form-control" id="viewStation" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Test Name</label>
                                <input type="text" class="form-control" id="viewTestName" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <input type="text" class="form-control" id="viewGender" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Age</label>
                                <input type="text" class="form-control" id="viewAge" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Birth Date</label>
                                <input type="text" class="form-control" id="viewBirthDate" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Requesting Physician</label>
                            <input type="text" class="form-control" id="viewPhysician" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Clinical Information</label>
                            <textarea class="form-control" id="viewClinicalInfo" rows="3" readonly></textarea>
                        </div>

                        <div class="reject-reason-container" id="rejectReasonContainer">
    <label class="form-label">Reason for Rejection</label>
    <textarea class="form-control" id="rejectReason" name="reject_reason" rows="3" 
              placeholder="Please specify the reason for rejection..." required></textarea>
</div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger reject-btn">Reject</button>
                    <button type="button" class="btn btn-success approve-btn">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
// View Request Modal Handler
$('#viewRequestModal').on('show.bs.modal', function(event) {
    const button = $(event.relatedTarget);
    const modal = $(this);
    
    // Reset reject reason UI
    $('#rejectReasonContainer').hide();
    $('#rejectReason').val('');
    $('.reject-btn').text('Reject');
    
    // Set all the data attributes to modal fields
    modal.find('#requestId').val(button.data('id'));
    modal.find('#patientId').val(button.data('patient-id'));
    modal.find('#sampleId').val(button.data('sample-id'));
    modal.find('#viewDate').val(button.data('date'));
    modal.find('#viewPatientId').val(button.data('patient-id'));
    modal.find('#viewSampleId').val(button.data('sample-id') || 'N/A');
    modal.find('#viewName').val(button.data('name'));
    modal.find('#viewStation').val(button.data('station'));
    modal.find('#viewTestName').val(button.data('test-name'));
    modal.find('#viewGender').val(button.data('gender'));
    modal.find('#viewAge').val(button.data('age'));
    modal.find('#viewBirthDate').val(button.data('birth-date'));
    modal.find('#viewPhysician').val(button.data('physician') || 'N/A');
    modal.find('#viewClinicalInfo').val(button.data('clinical-info') || 'N/A');
});

// Approve Action with Loading State and Better JSON Handling
$('#viewRequestModal').on('click', '.approve-btn', async function() {
    const modal = $('#viewRequestModal');
    const btn = $(this);
    const requestId = modal.find('#requestId').val();
    const patientId = modal.find('#patientId').val();
    const sampleId = modal.find('#sampleId').val();
    const patientName = modal.find('#viewName').val();
    const row = $(`button[data-id="${requestId}"]`).closest('tr');
    
    // Set loading state
    btn.html('<span class="spinner-border spinner-border-sm"></span> Processing')
       .prop('disabled', true);
    
    try {
        const response = await fetch('process_request_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': '<?= $csrf_token ?>'
            },
            body: new URLSearchParams({
    action: 'approve',
    patient_id: patientId,
    sample_id: sampleId,  // or null if not available
    csrf_token: '<?= $csrf_token ?>'
})
        });
        
        // First get the response text
        const responseText = await response.text();
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            throw new Error(`Invalid JSON response: ${responseText}`);
        }
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Approval failed without specific reason');
        }
        
        // Success handling
        row.fadeOut(400, function() {
            $(this).remove();
            
            // Update pending count
            const badge = $('.pending-badge');
            const newCount = parseInt(badge.text()) - 1;
            badge.text(newCount);
            $('.record-count').text(`Total Pending Requests: ${newCount}`);
            
            // Show success
            Swal.fire({
                title: 'Approved!',
                html: `Successfully approved request for:<br>
                       <b>${patientName}</b><br>
                       Patient ID: ${patientId}`,
                icon: 'success'
            }).then(() => {
                modal.modal('hide');
                // Reload if no more items on current page
                if ($('#pendingTableBody tr:visible').length === 0 && newCount > 0) {
                    window.location.href = `pendinglist.php?page=<?= $page ?>`;
                }
            });
        });
    } catch (error) {
        console.error('Approval error:', error);
        Swal.fire({
            title: 'Error!',
            html: `Failed to approve request for patient ${patientId}<br>
                   <b>Reason:</b> ${error.message}`,
            icon: 'error'
        });
    } finally {
        btn.html('Approve').prop('disabled', false);
    }
});

// $('#viewRequestModal').on('click', '.approve-btn', async function() {
//     const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
//     const modal = $('#viewRequestModal');
//     const patientId = modal.find('#patientId').val();
    
//     try {
//         const response = await fetch('process_request_action.php', {
//             method: 'POST',
//             body: new URLSearchParams({ patient_id: patientId })
//         });
        
//         const result = await response.json();
        
//         if (!result.success) {
//             throw new Error(result.message || 'Approval failed');
//         }
        
//         // Simple removal of row
//         btn.closest('tr').fadeOut();
//         $('.pending-badge').text(parseInt($('.pending-badge').text()) - 1);
        
//         Swal.fire('Success', 'Request approved', 'success');
//         modal.modal('hide');
        
//     } catch (error) {
//         Swal.fire('Error', error.message, 'error');
//     } finally {
//         btn.prop('disabled', false).html('Approve');
//     }
// });

// Single Reject Handler
$('#viewRequestModal').on('click', '.reject-btn', async function() {
    const modal = $('#viewRequestModal');
    const rejectBtn = $(this);
    const rejectReasonContainer = $('#rejectReasonContainer');
    const rejectReason = $('#rejectReason');
    
    // First click - show reason field
    if (!rejectReasonContainer.is(':visible')) {
        rejectReasonContainer.show();
        rejectReason.focus();
        rejectBtn.text('Confirm Rejection');
        return;
    }
    
    // Second click - validate and submit
    if (rejectReason.val().trim() === '') {
        await Swal.fire({
            title: 'Missing Reason',
            text: 'Please provide a reason for rejection',
            icon: 'warning'
        });
        return;
    }
    
    try {
        const requestId = modal.find('#requestId').val();
        const patientId = modal.find('#patientId').val();
        const sampleId = modal.find('#sampleId').val();
        const patientName = modal.find('#viewName').val();
        const reason = rejectReason.val().trim();
        const row = $(`button[data-id="${requestId}"]`).closest('tr');
        
        // Set loading state
        rejectBtn.html('<span class="spinner-border spinner-border-sm"></span> Processing')
               .prop('disabled', true);
        
        const response = await fetch('process_request_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': '<?= $csrf_token ?>'
            },
            body: new URLSearchParams({
                action: 'reject',
                request_id: requestId,
                patient_id: patientId,
                sample_id: sampleId,
                reject_reason: reason,
                csrf_token: '<?= $csrf_token ?>'
            })
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Rejection failed');
        }
        
        // Success handling
        row.fadeOut(400, function() {
            $(this).remove();
            
            // Update pending count
            const badge = $('.pending-badge');
            const newCount = parseInt(badge.text()) - 1;
            badge.text(newCount);
            $('.record-count').text(`Total Pending Requests: ${newCount}`);
            
            // Show success
            Swal.fire({
                title: 'Rejected!',
                html: `Successfully rejected request for:<br>
                       <b>${patientName}</b><br>
                       Patient ID: ${patientId}<br>
                       <b>Reason:</b> ${reason}`,
                icon: 'success'
            }).then(() => {
                modal.modal('hide');
                // Reload if no more items on current page
                if ($('#pendingTableBody tr:visible').length === 0 && newCount > 0) {
                    window.location.href = `pendinglist.php?page=<?= $page ?>`;
                }
            });
        });
    } catch (error) {
        Swal.fire({
            title: 'Error!',
            html: `Failed to reject request<br>
                   <b>Reason:</b> ${error.message}`,
            icon: 'error'
        });
    } finally {
        rejectBtn.html('Reject').prop('disabled', false);
    }
});

// Reset reject UI when modal closes
$('#viewRequestModal').on('hidden.bs.modal', function() {
    $('#rejectReasonContainer').hide();
    $('#rejectReason').val('');
    $('.reject-btn').text('Reject');
});

// // DELETE THIS DUPLICATE HANDLER
// $('#viewRequestModal').on('click', '.approve-btn', async function() {
//     const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
//     const modal = $('#viewRequestModal');
//     const patientId = modal.find('#patientId').val();
    
//     try {
//         const response = await fetch('process_request_action.php', {
//             method: 'POST',
//             body: new URLSearchParams({ patient_id: patientId })
//         });
        
//         const result = await response.json();
        
//         if (!result.success) {
//             throw new Error(result.message || 'Approval failed');
//         }
        
//         // Simple removal of row
//         btn.closest('tr').fadeOut();
//         $('.pending-badge').text(parseInt($('.pending-badge').text()) - 1);
        
//         Swal.fire('Success', 'Request approved', 'success');
//         modal.modal('hide');
        
//     } catch (error) {
//         Swal.fire('Error', error.message, 'error');
//     } finally {
//         btn.prop('disabled', false).html('Approve');
//     }
// });

document.querySelector('.reject-btn').addEventListener('click', function () {
    document.getElementById('rejectReasonContainer').style.display = 'block';
    const requestId = document.getElementById('requestId').value;
    const rejectReason = document.getElementById('rejectReason').value;
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    if (!rejectReason.trim()) {
        alert("Please provide a reason for rejection.");
        return;
    }

    fetch('reject-request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            request_id: requestId,
            reject_reason: rejectReason,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Rejected', 'Request has been rejected.', 'success').then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Something went wrong.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});


// Search functionality (client-side for current page only)
$('#searchPending').on('keyup', function() {
    const value = $(this).val().toLowerCase();
    $('#pendingTableBody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});

// for filtering sa table 
document.getElementById('statusFilter').addEventListener('change', function() {
    const status = this.value;
    const rows = document.querySelectorAll('#pendingTableBody tr');
    
    rows.forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            const statusCell = row.querySelector('td:nth-child(9)').textContent.trim();
            row.style.display = statusCell.includes(status) ? '' : 'none';
        }
    });
    
    const visibleRows = document.querySelectorAll('#pendingTableBody tr:not([style*="display: none"])').length;
    document.querySelector('.record-count').textContent = `Visible Requests: ${visibleRows} (Total: ${rows.length})`;
});
</script>
</body>
</html>
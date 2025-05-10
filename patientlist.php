<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

try {
    // Get total count of patients
    $countStmt = $connect->query("SELECT COUNT(*) FROM patients");
    $totalPatients = $countStmt->fetchColumn();

    // Get current page of patients
    $stmt = $connect->prepare("SELECT * FROM patients ORDER BY full_name LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($totalPatients / $recordsPerPage);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get current user data for profile editing
try {
    $userStmt = $connect->prepare("SELECT * FROM users WHERE username = :username");
    $userStmt->bindParam(':username', $username);
    $userStmt->execute();
    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $currentUser['id'] ?? null;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Klinika Papaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
        }

        .main-content {
            margin-left: 0px;
            margin-right: 450px;
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
            margin-left: 300px;
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
    </style>
</head>
<body>
    <!-- Header with Dropdown -->
    <header>
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($username) ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </a></li>
                <?php if ($role === 'admin'): ?>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add User
                </a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php" id="logoutBtn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a></li>
            </ul>
        </div>
    </header>

    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="table-container">
            <h2>PATIENT LIST</h2>

            <!-- Record Count Display -->
            <div class="record-count">
                Total Patients: <?= $totalPatients ?>
            </div>

            <!-- Add Patient Button: Trigger Modal -->
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Add Patient</button>

            <!-- Search Bar -->
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="Search patients...">
            </div>

            <!-- Table -->
            <table id="patientTable" class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Patient ID</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Date of Birth</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $row): ?>
                        <tr id="patient-row-<?= $row['patient_id'] ?>">
                            <td><?= htmlspecialchars($row['patient_id']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['gender']) ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['birth_date']) ?></td>
                            <td>
                                <!-- Edit Button: Trigger Modal -->
                                <button class="btn btn-sm btn-primary edit-btn" 
                                        data-id="<?= $row['patient_id'] ?>" 
                                        data-name="<?= $row['full_name'] ?>" 
                                        data-gender="<?= $row['gender'] ?>" 
                                        data-age="<?= $row['age'] ?>" 
                                        data-birth="<?= $row['birth_date'] ?>">Edit</button>

                                <!-- Delete Button -->
                                <button class="btn btn-sm btn-danger delete-btn" 
                                        data-id="<?= $row['patient_id'] ?>" 
                                        data-name="<?= $row['full_name'] ?>">Delete</button>

                                <!-- Request Button -->
                                <button class="btn btn-sm btn-info request-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#requestModal"
                                        data-patient-id="<?= $row['patient_id'] ?>"
                                        data-name="<?= $row['full_name'] ?>"
                                        data-gender="<?= $row['gender'] ?>"
                                        data-age="<?= $row['age'] ?>"
                                        data-birth="<?= $row['birth_date'] ?>">Request</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No patients found.</td></tr>
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

    <!-- Edit Patient Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="edit_patient.php">
                        <input type="hidden" name="patient_id" id="patient_id">

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="full_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" required>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Update Patient</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this patient: <span id="delete_patient_name"></span>?
                    <input type="hidden" id="delete_patient_id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="deletePatient($('#delete_patient_id').val())">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_patient.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="full_name" placeholder="Enter full name" required>
                        </div>

                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="" disabled selected>Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Add Patient</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="edit_profile.php">
                        <input type="hidden" name="user_id" value="<?= $user_id ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        
                        <?php if ($role === 'admin'): ?>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="admin" <?= ($currentUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="staff" <?= ($currentUser['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Request -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Request Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="requestForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" id="patientId" name="patient_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Patient ID</label>
                        <input type="text" class="form-control" id="displayPatientId" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Patient Name</label>
                        <input type="text" class="form-control" id="patientName" name="name" readonly>
                    </div>
                    
                    <!-- Added Sample ID Field -->
                    <div class="mb-3">
                        <label for="sampleId" class="form-label">Sample ID*</label>
                        <input type="text" class="form-control" id="sampleId" name="sample_id" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="testName" class="form-label">Test Name*</label>
                        <select class="form-select" id="testName" name="test_name" required>
                            <option value="">Select Test</option>
                            <option value="CBC">Complete Blood Count (CBC)</option>
                            <option value="Urinalysis">Urinalysis</option>
                            <option value="Blood Chemistry">Blood Chemistry</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="station" class="form-label">Station/Ward*</label>
                        <input type="text" class="form-control" id="station" name="station" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="physician" class="form-label">Requesting Physician</label>
                        <input type="text" class="form-control" id="physician" name="physician">
                    </div>
                    
                    <div class="mb-3">
                        <label for="clinical_info" class="form-label">Clinical Information</label>
                        <textarea class="form-control" id="clinical_info" name="clinical_info" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Add User Modal (Admin Only) -->
    <?php if ($role === 'admin'): ?>
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="add_user.php">
                        <div class="mb-3">
                            <label for="new_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="new_username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="new_password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_role" class="form-label">Role</label>
                            <select class="form-select" id="new_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Request Modal Handler
        $('#requestModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const modal = $(this);
            
            modal.find('#patientId').val(button.data('patient-id'));
    modal.find('#displayPatientId').val(button.data('patient-id'));
    modal.find('#patientName').val(button.data('name'));
    
    // Generate a sample ID if not provided
    const sampleId = 'LAB-' + Date.now().toString().slice(-6);
    modal.find('#sampleId').val(sampleId);
        });

        // Request Form Submission
        $('#requestForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            
            submitBtn.prop('disabled', true)
                     .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

            $.ajax({
                url: 'process_request.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
                        $('#requestModal').modal('hide');
                        form.trigger('reset');
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            })
            .fail(function() {
                Swal.fire('Error!', 'Failed to submit request. Please try again.', 'error');
            })
            .always(function() {
                submitBtn.prop('disabled', false).text('Submit Request');
            });
        });

        // Edit Modal Handler
        $('#editModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const modal = $(this);
            modal.find('#patient_id').val(button.data('id'));
            modal.find('#name').val(button.data('name'));
            modal.find('#gender').val(button.data('gender'));
            modal.find('#age').val(button.data('age'));
            modal.find('#birth_date').val(button.data('birth'));
        });

        // Delete Modal Handler
        $('#deleteModal').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget);
            const modal = $(this);
            modal.find('#delete_patient_id').val(button.data('id'));
            modal.find('#delete_patient_name').text(button.data('name'));
        });

        // Delete Patient function
        function deletePatient(patientId) {
            const patientName = $('#delete_patient_name').text();
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete patient: ${patientName}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('delete_patient.php', { 
                        patient_id: patientId,
                        csrf_token: '<?= $csrf_token ?>'
                    }, function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            ).then(() => {
                                $('#patient-row-' + patientId).remove();
                                $('#deleteModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    }, 'json').fail(function() {
                        Swal.fire(
                            'Error!',
                            'Error deleting patient. Please try again.',
                            'error'
                        );
                    });
                }
            });
        }

        // Logout confirmation
        $('#logoutBtn').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            
            Swal.fire({
                title: 'Logout?',
                text: 'Are you sure you want to log out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log out'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#tableBody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Show success/error messages on page load
        $(document).ready(function() {
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?>
                    Swal.fire({
                        title: 'Success!',
                        text: '<?= $_GET['message'] ?? 'Operation completed successfully' ?>',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                <?php elseif ($_GET['status'] == 'error'): ?>
                    Swal.fire({
                        title: 'Error!',
                        text: '<?= $_GET['message'] ?? 'Something went wrong. Please try again.' ?>',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
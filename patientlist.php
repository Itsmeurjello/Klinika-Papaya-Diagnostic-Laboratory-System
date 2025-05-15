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

$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

try {
    $countStmt = $connect->query("SELECT COUNT(*) FROM patients");
    $totalPatients = $countStmt->fetchColumn();

    $stmt = $connect->prepare("SELECT * FROM patients ORDER BY full_name LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($totalPatients / $recordsPerPage);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

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
                Total Patients: <span id="patientCount"><?= $totalPatients ?></span>
            </div>

            <?php if ($role === 'admin'): ?>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addModal">Add Patient</button>
            <?php endif; ?>
            
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
                                        data-name="<?= htmlspecialchars($row['full_name']) ?>" 
                                        data-gender="<?= htmlspecialchars($row['gender']) ?>" 
                                        data-age="<?= htmlspecialchars($row['age']) ?>" 
                                        data-birth="<?= htmlspecialchars($row['birth_date']) ?>">Edit</button>

                                <!-- Delete Button -->
                                <button class="btn btn-sm btn-danger delete-btn" 
                                        data-id="<?= $row['patient_id'] ?>" 
                                        data-name="<?= htmlspecialchars($row['full_name']) ?>">Delete</button>

                                <!-- Request Button -->
                                <button class="btn btn-sm btn-info request-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#requestModal"
                                        data-patient-id="<?= $row['patient_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['full_name']) ?>"
                                        data-gender="<?= htmlspecialchars($row['gender']) ?>"
                                        data-age="<?= htmlspecialchars($row['age']) ?>"
                                        data-birth="<?= htmlspecialchars($row['birth_date']) ?>">Request</button>
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
                    <form id="editPatientForm">
                        <input type="hidden" name="patient_id" id="edit_patient_id">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_name" name="full_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_gender" class="form-label">Gender</label>
                            <select class="form-select" id="edit_gender" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit_age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="edit_age" name="age" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_birth_date" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="edit_birth_date" name="birth_date" required>
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
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
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
                    <form id="addPatientForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                            <label for="add_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="add_name" name="full_name" placeholder="Enter full name" required>
                        </div>

                        <div class="mb-3">
                            <label for="add_gender" class="form-label">Gender</label>
                            <select class="form-select" id="add_gender" name="gender" required>
                                <option value="" disabled selected>Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="add_age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="add_age" name="age" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="add_birth_date" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="add_birth_date" name="birth_date" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Add Patient</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Modal -->
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
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
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
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            const addPatientForm = document.getElementById('addPatientForm');
            addPatientForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const submitBtn = addPatientForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
                
                try {
                    const formData = new FormData(addPatientForm);
                    
                    const response = await fetch('add_patient.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        const newPatientRow = document.createElement('tr');
                        newPatientRow.id = `patient-row-${result.patient.patient_id}`;
                        
                        newPatientRow.innerHTML = `
                            <td>${result.patient.patient_id}</td>
                            <td>${result.patient.full_name}</td>
                            <td>${result.patient.gender}</td>
                            <td>${result.patient.age}</td>
                            <td>${result.patient.birth_date}</td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-btn" 
                                        data-id="${result.patient.patient_id}" 
                                        data-name="${result.patient.full_name}" 
                                        data-gender="${result.patient.gender}" 
                                        data-age="${result.patient.age}" 
                                        data-birth="${result.patient.birth_date}">Edit</button>

                                <button class="btn btn-sm btn-danger delete-btn" 
                                        data-id="${result.patient.patient_id}" 
                                        data-name="${result.patient.full_name}">Delete</button>

                                <button class="btn btn-sm btn-info request-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#requestModal"
                                        data-patient-id="${result.patient.patient_id}"
                                        data-name="${result.patient.full_name}"
                                        data-gender="${result.patient.gender}"
                                        data-age="${result.patient.age}"
                                        data-birth="${result.patient.birth_date}">Request</button>
                            </td>
                        `;
                        
                        const tableBody = document.getElementById('tableBody');
                        if (tableBody.firstChild) {
                            tableBody.insertBefore(newPatientRow, tableBody.firstChild);
                        } else {
                            tableBody.appendChild(newPatientRow);
                        }
                        
                        const countElement = document.getElementById('patientCount');
                        const currentCount = parseInt(countElement.textContent);
                        countElement.textContent = currentCount + 1;
                        
                        addPatientForm.reset();
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                        addModal.hide();
                        
                        attachEventHandlers();
                    } else {
                        let errorMessage = result.message;
                        
                        if (result.errors && result.errors.length > 0) {
                            errorMessage += ':<br>' + result.errors.join('<br>');
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while adding the patient. Please try again.'
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
            
            const editPatientForm = document.getElementById('editPatientForm');
            editPatientForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const submitBtn = editPatientForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
                
                try {
                    const formData = new FormData(editPatientForm);
                    const patientId = formData.get('patient_id');
                    
                    const response = await fetch('edit_patient.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        const row = document.getElementById(`patient-row-${patientId}`);
                        if (row) {
                            row.cells[1].textContent = formData.get('full_name');
                            row.cells[2].textContent = formData.get('gender');
                            row.cells[3].textContent = formData.get('age');
                            row.cells[4].textContent = formData.get('birth_date');
                            
                            const editBtn = row.querySelector('.edit-btn');
                            editBtn.dataset.name = formData.get('full_name');
                            editBtn.dataset.gender = formData.get('gender');
                            editBtn.dataset.age = formData.get('age');
                            editBtn.dataset.birth = formData.get('birth_date');
                            
                            const deleteBtn = row.querySelector('.delete-btn');
                            deleteBtn.dataset.name = formData.get('full_name');
                            
                            const requestBtn = row.querySelector('.request-btn');
                            requestBtn.dataset.name = formData.get('full_name');
                            requestBtn.dataset.gender = formData.get('gender');
                            requestBtn.dataset.age = formData.get('age');
                            requestBtn.dataset.birth = formData.get('birth_date');
                        }
                        
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                        editModal.hide();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: result.message || 'Failed to update patient.'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while updating the patient. Please try again.'
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
            
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            confirmDeleteBtn.addEventListener('click', async function() {
                const patientId = document.getElementById('delete_patient_id').value;
                const patientName = document.getElementById('delete_patient_name').textContent;
                
                Swal.fire({
                    title: 'Deleting...',
                    html: 'Please wait while we process your request.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                try {
                    const formData = new FormData();
                    formData.append('patient_id', patientId);
                    formData.append('csrf_token', '<?= $csrf_token ?>');
                    
                    const response = await fetch('delete_patient.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const row = document.getElementById(`patient-row-${patientId}`);
                        if (row) {
                            row.style.transition = 'opacity 0.5s';
                            row.style.opacity = '0';
                            
                            setTimeout(() => {
                                row.remove();
                                
                                const countElement = document.getElementById('patientCount');
                                const currentCount = parseInt(countElement.textContent);
                                countElement.textContent = currentCount - 1;
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: result.message || `Patient ${patientName} has been deleted.`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }, 500);
                        }
                        
                        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                        deleteModal.hide();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: result.message || 'Failed to delete patient.'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while deleting the patient. Please try again.'
                    });
                }
            });
            
            const requestForm = document.getElementById('requestForm');
            requestForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const submitBtn = requestForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                
                try {
                    const formData = new FormData(requestForm);
                    
                    const response = await fetch('process_request.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: result.message,
                            icon: 'success'
                        }).then(() => {
                            requestForm.reset();
                            const requestModal = bootstrap.Modal.getInstance(document.getElementById('requestModal'));
                            requestModal.hide();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: result.message,
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to submit request. Please try again.',
                        icon: 'error'
                    });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
            
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('keyup', function() {
                const value = this.value.toLowerCase();
                const rows = document.querySelectorAll('#tableBody tr');
                
                rows.forEach(row => {
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(value) ? '' : 'none';
                });
            });
            
            const logoutBtn = document.getElementById('logoutBtn');
            logoutBtn.addEventListener('click', function(event) {
                event.preventDefault();
                const href = this.getAttribute('href');
                
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
            
            function attachEventHandlers() {
                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const patientId = this.dataset.id;
                        const name = this.dataset.name;
                        const gender = this.dataset.gender;
                        const age = this.dataset.age;
                        const birthDate = this.dataset.birth;
                        
                        document.getElementById('edit_patient_id').value = patientId;
                        document.getElementById('edit_name').value = name;
                        document.getElementById('edit_gender').value = gender;
                        document.getElementById('edit_age').value = age;
                        document.getElementById('edit_birth_date').value = birthDate;
                        
                        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                        editModal.show();
                    });
                });
                
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const patientId = this.dataset.id;
                        const name = this.dataset.name;
                        
                        document.getElementById('delete_patient_id').value = patientId;
                        document.getElementById('delete_patient_name').textContent = name;
                        
                        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                        deleteModal.show();
                    });
                });
                
                document.querySelectorAll('.request-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const patientId = this.dataset.patientId;
                        const name = this.dataset.name;
                        const gender = this.dataset.gender;
                        const age = this.dataset.age;
                        const birthDate = this.dataset.birth;
                        
                        document.getElementById('patientId').value = patientId;
                        document.getElementById('displayPatientId').value = patientId;
                        document.getElementById('patientName').value = name;
                        
                        const sampleId = 'LAB-' + Date.now().toString().slice(-6);
                        document.getElementById('sampleId').value = sampleId;
                    });
                });
            }
            
            attachEventHandlers();
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) {
                const status = urlParams.get('status');
                const message = urlParams.get('message') || '';
                
                if (status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: message || 'Operation completed successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else if (status === 'error') {
                    Swal.fire({
                        title: 'Error!',
                        text: message || 'Something went wrong. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    </script>
</body>
</html>
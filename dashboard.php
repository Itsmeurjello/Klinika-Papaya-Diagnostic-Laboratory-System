<?php 
require_once 'config/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? '';

$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $recordsPerPage;

try {
    $countStmt = $connect->query("SELECT COUNT(*) FROM patients");
    $totalPatients = $countStmt->fetchColumn();

    $stmt = $connect->prepare("SELECT * FROM patients LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($totalPatients / $recordsPerPage);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
$doctorsAtWork = 5; 
$topTreatments = [
    ['name' => 'Treatment A', 'count' => 25],
    ['name' => 'Treatment B', 'count' => 20],
    ['name' => 'Treatment C', 'count' => 15]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Klinika Papaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f1f3f5;
            padding-top: 0px; /* Add space for fixed header */
        }
        
        /* Fixed header styling */
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

        .main-content {
            margin-left: 250px; /* Same as sidebar width */
            padding: 50px;
            width: calc(100% - 250px);
        }
        
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-title {
            font-size: 16px;
            color: #6c757d;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #343a40;
        }
        .analytics-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .analytics-title {
            font-size: 18px;
            color: #6c757d;
        }
        .analytics-value {
            font-size: 24px;
            font-weight: bold;
            color: #343a40;
        }
        .treatment-list {
            list-style-type: none;
            padding: 0;
        }
        .treatment-list li {
            margin: 8px 0;
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
            <li><a class="dropdown-item" href="logout.php" onclick="return confirm('Are you sure you want to log out?')">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a></li>
        </ul>
    </div>
</header>


<?php include('sidebar.php'); ?>

<div class="main-content mt-4">
    <div class="row g-4">
        <!-- Total Patients -->
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-title">Total Patients</div>
                <div class="stat-value"><?= $totalPatients ?></div>
            </div>
        </div>

        <!-- Doctors at Work -->
        <div class="col-md-6 col-lg-3">
            <div class="analytics-card">
                <div class="analytics-title">Doctors at Work</div>
                <div class="analytics-value"><?= $doctorsAtWork ?></div>
            </div>
        </div>

        <!-- Top Treatments -->
        <div class="col-md-6 col-lg-6">
            <div class="analytics-card">
                <div class="analytics-title">Top Treatments</div>
                <ul class="treatment-list">
                    <?php foreach ($topTreatments as $treatment): ?>
                        <li><strong><?= $treatment['name'] ?></strong> - <?= $treatment['count'] ?> treatments</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Patient List -->
    <div class="mt-4">
        <h3>Patient List</h3>
        <table class="table table-striped" id="patient-table">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Full Name</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Date of Birth</th>
                    <!-- <th>Action</th> -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                <tr id="patient-<?php echo $patient['patient_id']; ?>">
                    <td><?php echo $patient['patient_id']; ?></td>
                    <td><?php echo $patient['full_name']; ?></td>
                    <td><?php echo $patient['gender']; ?></td>
                    <td><?php echo $patient['age']; ?></td>
                    <td><?php echo $patient['birth_date']; ?></td>
                    <!-- <td>
                        <button class="btn btn-danger delete-btn" data-id="<?php echo $patient['patient_id']; ?>" data-name="<?php echo $patient['full_name']; ?>">Delete</button>
                    </td> -->
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="patient-name"></strong>?
            </div>
            <div class="modal-footer">
                <form id="delete-form" method="POST">
                    <input type="hidden" name="patient_id" id="patient-id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // JavaScript to fill the modal with the patient info
    $('#deleteModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var patientId = button.data('id');
        var patientName = button.data('name');
        
        // Update the modal's content
        $('#patient-name').text(patientName);
        $('#patient-id').val(patientId);
    });

    // Handle the form submission using AJAX
    $('#delete-form').on('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting normally

        var patientId = $('#patient-id').val();

        $.ajax({
            url: 'delete_patient.php',
            method: 'POST',
            data: { patient_id: patientId },
            success: function(response) {
                if (response == 'success') {
                    // Remove the patient row from the table
                    $('#patient-' + patientId).remove();
                    // Close the modal
                    $('#deleteModal').modal('hide');
                } else {
                    alert('Error deleting patient!');
                }
            }
        });
    });

    // Attach click event for Delete buttons
    $('.delete-btn').on('click', function() {
        var patientId = $(this).data('id');
        var patientName = $(this).data('name');
        $('#patient-name').text(patientName);
        $('#patient-id').val(patientId);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

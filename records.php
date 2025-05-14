<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get filter values
$searchTerm = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$testFilter = $_GET['test'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query conditions
$conditions = [];
$params = [];

if (!empty($searchTerm)) {
    $conditions[] = "(test_name LIKE :search OR patient_name LIKE :search OR sample_id LIKE :search)";
    $params[':search'] = "%$searchTerm%";
}

if ($statusFilter != 'all') {
    $conditions[] = "status = :status";
    $params[':status'] = $statusFilter;
}

if ($testFilter != 'all') {
    $conditions[] = "test_name = :test";
    $params[':test'] = $testFilter;
}

if (!empty($dateFrom)) {
    $conditions[] = "test_date >= :date_from";
    $params[':date_from'] = $dateFrom . " 00:00:00";
}

if (!empty($dateTo)) {
    $conditions[] = "test_date <= :date_to";
    $params[':date_to'] = $dateTo . " 23:59:59";
}

// Combine conditions
$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;

try {
    // Get total count of records
    $countSql = "SELECT COUNT(*) FROM test_records $whereClause";
    $countStmt = $connect->prepare($countSql);
    
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);

    // Get test types for filter dropdown
    $testTypesStmt = $connect->query("SELECT DISTINCT test_name FROM test_records ORDER BY test_name");
    $testTypes = $testTypesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get records for current page
    $sql = "SELECT * FROM test_records $whereClause ORDER BY test_date DESC LIMIT :limit OFFSET :offset";
    $stmt = $connect->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
    <body>
        <header>
            <a href="logout.php" onclick="return confirm('Log out?')">Logout</a>
        </header>

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="table-container">
            <h2>Test Records</h2>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by test name, patient or sample ID" 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $statusFilter == 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="Pending" <?php echo $statusFilter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo $statusFilter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo $statusFilter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="test" class="form-label">Test Type</label>
                        <select class="form-select" id="test" name="test">
                            <option value="all" <?php echo $testFilter == 'all' ? 'selected' : ''; ?>>All Tests</option>
                            <?php foreach ($testTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $testFilter == $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="records.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Record Count -->
            <div class="record-count">
                Total Records: <?php echo $totalRecords; ?>
            </div>

            <!-- Records Table -->
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Test Name</th>
                        <th>Patient Name</th>
                        <th>Sample ID</th>
                        <th>Section</th>
                        <th>Test Date</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['id']); ?></td>
                                <td><?php echo htmlspecialchars($record['test_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['sample_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['section']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($record['test_date']))); ?></td>
                                <td>
                                    <?php if ($record['status'] == 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($record['status'] == 'In Progress'): ?>
                                        <span class="badge bg-info">In Progress</span>
                                    <?php elseif ($record['status'] == 'Completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo !empty($record['result']) ? htmlspecialchars(substr($record['result'], 0, 30)) . '...' : 'N/A'; ?></td>
                                <td>
                                    <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if ($record['status'] != 'Completed'): ?>
                                        <a href="update_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-primary">Update</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>&status=<?= $statusFilter ?>&test=<?= $testFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>&status=<?= $statusFilter ?>&test=<?= $testFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>&status=<?= $statusFilter ?>&test=<?= $testFilter ?>&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Date filter validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
                e.preventDefault();
                alert('Date From cannot be after Date To');
            }
        });
    </script>
</body>
</html>
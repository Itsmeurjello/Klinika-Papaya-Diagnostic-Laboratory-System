<?php
session_start();
require_once 'config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check for approval success message
$approval_success = false;
if (isset($_GET['approval_success']) && $_GET['approval_success'] == '1') {
    $approval_success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request List - Klinika Papaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
            width: 1200px;
        }
        table {
            width: 100%;
            font-size: 16px;
        }
        h2 {
            margin-bottom: 20px;
        }
        .record-count {
            font-weight: bold;
            margin-bottom: 15px;
        }
        .action-btns .btn {
            margin: 2px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
            <h2>APPROVED REQUEST LIST</h2>

            <?php if ($approval_success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Request was successfully approved!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Record Count Display -->
            <div class="record-count">
                <?php
                try {
                    $countStmt = $connect->prepare("SELECT COUNT(*) AS total FROM request_list WHERE status = 'Approved'");
                    $countStmt->execute();
                    $rowCount = $countStmt->fetch(PDO::FETCH_ASSOC);
                    echo "Total Approved Requests: " . htmlspecialchars($rowCount['total']);
                } catch (PDOException $e) {
                    echo "Error fetching request count.";
                    error_log("Database error: " . $e->getMessage());
                }
                ?>
            </div>
            
            <!-- Search Bar -->
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="Search approved requests...">
            </div>

            <!-- Request Table -->
            <table id="requestTable" class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Patient ID</th>
                        <th>Sample ID</th>
                        <th>Name</th>
                        <th>Station/Ward</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Date of Birth</th>
                        <th>Test Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    try {
                        $stmt = $connect->prepare("
                            SELECT * FROM request_list 
                            WHERE status = 'Approved'
                            ORDER BY request_date DESC
                        ");
                        $stmt->execute();
                        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($requests) > 0):
                            foreach ($requests as $row):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['request_date']) ?></td>
                        <td><?= htmlspecialchars($row['patient_id']) ?></td>
                        <td><?= htmlspecialchars($row['sample_id']) ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['station_ward']) ?></td>
                        <td><?= htmlspecialchars($row['gender']) ?></td>
                        <td><?= htmlspecialchars($row['age']) ?></td>
                        <td><?= htmlspecialchars($row['birth_date']) ?></td>
                        <td><?= htmlspecialchars($row['test_name']) ?></td>
                        <td class="action-btns">
                            <a href="test.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Test</a>
                            <a href="move_to_pending.php?id=<?= $row['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?? '' ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Move this request back to pending?')">Pending</a>
                        </td>
                    </tr>
                    <?php
                            endforeach;
                        else:
                    ?>
                    <tr><td colspan="10" class="text-center">No approved requests found.</td></tr>
                    <?php endif; 
                    } catch (PDOException $e) {
                        error_log("Database error: " . $e->getMessage());
                        echo '<tr><td colspan="10" class="text-center">Error loading requests</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-center align-items-center mt-3">
                <button id="prevBtn" class="btn btn-outline-primary me-2">Previous</button>
                <span id="pageIndicator" class="fw-bold text-secondary mx-2">Page 1 of 1</span>
                <button id="nextBtn" class="btn btn-outline-primary ms-2">Next</button>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['moved_to_pending'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        Request moved back to pending.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Script: Search + Pagination -->
    <script>
        $(document).ready(function() {
            const rowsPerPage = 10;
            let currentPage = 1;
            let totalRows = $('#tableBody tr').length;
            let totalPages = Math.ceil(totalRows / rowsPerPage);

            // Initialize pagination
            updatePagination();

            // Search functionality
            $('#searchInput').on('keyup', function() {
                const searchText = $(this).val().toLowerCase();
                $('#tableBody tr').each(function() {
                    const rowText = $(this).text().toLowerCase();
                    $(this).toggle(rowText.includes(searchText));
                });
                totalRows = $('#tableBody tr:visible').length;
                totalPages = Math.ceil(totalRows / rowsPerPage);
                currentPage = 1;
                updatePagination();
            });

            // Pagination controls
            $('#prevBtn').click(function() {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                }
            });

            $('#nextBtn').click(function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    updatePagination();
                }
            });

            function updatePagination() {
                // Update page indicator
                $('#pageIndicator').text(`Page ${currentPage} of ${totalPages}`);

                // Enable/disable pagination buttons
                $('#prevBtn').prop('disabled', currentPage <= 1);
                $('#nextBtn').prop('disabled', currentPage >= totalPages);

                // Show/hide rows based on current page
                const startIdx = (currentPage - 1) * rowsPerPage;
                const endIdx = startIdx + rowsPerPage;
                
                $('#tableBody tr:visible').each(function(index) {
                    $(this).toggle(index >= startIdx && index < endIdx);
                });
            }

            // Auto-close alert after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>
</html>
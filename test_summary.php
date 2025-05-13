<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Summary</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            padding-top: 20px;
        }

        .sidebar a {
            text-decoration: none;
            color: #000;
            padding: 10px 15px;
            display: block;
        }

        .sidebar a:hover {
            background-color: #dc3545;
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .test-summary-container {
            margin-top: 30px;
        }

        .test-summary-table {
            margin-top: 20px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        header {
            background-color: #f8f9fa;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 250px;
            width: calc(100% - 250px);
            z-index: 1000;
        }

        header a {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    
        <!-- Header -->
    <header>
        <a href="logout.php" onclick="return confirm('Log out?')">Logout</a>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <?php include('sidebar.php'); ?>
    </div>

    

    <!-- Main Content -->
    <div class="main-content">
        <div class="test-summary-container">
            <h2>Test Summary</h2>

            <!-- Search Bar -->
            <form method="GET" class="d-flex mb-3">
                <input type="text" class="form-control" name="search" placeholder="Search by Test Name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit" class="btn btn-primary ms-2">Search</button>
            </form>

            <!-- Error Message Display -->
            <?php if(isset($errMsg)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Test Summary Table -->
            <table class="table table-bordered test-summary-table">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Test Date</th>
                        <th>Result</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($testSummaries) > 0): ?>
                        <?php foreach ($testSummaries as $testSummary): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($testSummary['test_name']); ?></td>
                                <td><?php echo htmlspecialchars($testSummary['test_date']); ?></td>
                                <td><?php echo htmlspecialchars($testSummary['result']); ?></td>
                                <td><?php echo htmlspecialchars($testSummary['remarks']); ?></td>
                                <td>
                                    <a href="edit-test-summary.php?id=<?php echo $testSummary['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="delete-test-summary.php?id=<?php echo $testSummary['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No test summaries available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <nav>
                    <ul class="pagination">
                        <li class="page-item"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

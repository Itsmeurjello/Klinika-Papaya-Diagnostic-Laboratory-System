<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Sidebar Styles (same as patientlist.php) */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            padding-top: 20px;
            text-align: center; /* Centering sidebar links */
        }

        .sidebar a {
            text-decoration: none;
            color: #000;
            padding: 10px 15px;
            display: block;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Main content area */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            text-align: center; /* Centering the text in the main content */
        }

        .container-fluid {
            padding-top: 30px;
        }

        .btn {
            margin-right: 10px;
        }

        .btn-primary, .btn-success, .btn-warning, .btn-info {
            text-align: center; /* Center button text */
        }

        .card-body {
            text-align: center; /* Center the content inside the card */
        }

        .modal-body {
            text-align: center; /* Center content inside the modal */
        }

        .modal-header {
            text-align: center; /* Center modal header text */
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <?php include('sidebar.php'); ?>
    </div>

    <div class="main-content p-4">
        <div class="container-fluid">
            <h2 class="mb-4">Reports Sales for that day</h2>

            <div class="mb-4 d-flex flex-wrap justify-content-center gap-3">
                <a href="income.php" class="btn btn-primary">Income</a>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#censusModal">Census</button>
                <a href="workload.php" class="btn btn-warning text-white">Workload</a>
                <a href="consumption.php" class="btn btn-info text-white">Consumption</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <p>Select a category above to view records.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="censusModal" tabindex="-1" aria-labelledby="censusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="censusModalLabel">Census Options</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="groupSelect" class="form-label">Select Group</label>
                            <select id="groupSelect" class="form-select">
                                <option selected disabled>Choose a group</option>
                                <option value="Group A">Group A</option>
                                <option value="Group B">Group B</option>
                                <option value="Group C">Group C</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="stationSelect" class="form-label">Select Station</label>
                            <select id="stationSelect" class="form-select">
                                <option selected disabled>Choose a station</option>
                                <option value="Station 1">Station 1</option>
                                <option value="Station 2">Station 2</option>
                                <option value="Station 3">Station 3</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Masterlist Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css"> <!-- Your custom CSS -->
</head>
<body>
<?php include('sidebar.php'); ?>

<div class="main-content p-4" style="margin-left: 250px;">
    <div class="container-fluid">
        <h2 class="mb-4">Test Masterlist Setup</h2>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="testOrderFilter" class="form-control" placeholder="Test Order">
            </div>
            <div class="col-md-4">
                <input type="text" id="testNameFilter" class="form-control" placeholder="Test">
            </div>
            <div class="col-md-4">
                <input type="text" id="sectionFilter" class="form-control" placeholder="Section">
            </div>
        </div>

        <!-- Record Count -->
        <div class="mb-2">
            <span id="recordCount" class="badge bg-primary">Total Records: 0</span>
        </div>

        <!-- Masterlist Table -->
        <div class="table-responsive">
            <table id="testMasterlist" class="table table-bordered table-hover">
                <thead class="table-light">
                <tr>
                    <th>Select</th>
                    <th>Test Order</th>
                    <th>Test</th>
                    <th>Full Name</th>
                    <th>Remarks</th>
                    <th>Section</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $testData = [
                    ["1st 7th DOTT", "Oral Glucose Retensin Test", "ORTEGA, JELLO", "Routine", "Chemistry"],
                    ["2nd 7th DOTT", "Oral Glucose Tolerance Test", "MADRID, FRANCE PAUL", "Urgent", "Chemistry"],
                    ["RIS / NOT", "Random Blend Saga", "PERALTA, LEEJIM", "Stat", "Clinical Microscopy"],
                    ["CHRL", "Total Charlsonral", "GAMIT, DENMAR", "Routine", "Hematology"],
                    ["Triglycerides", "Triglycerides", "SOLARTE, KURT LEE", "Routine", "Chemistry"]
                ];

                foreach ($testData as $row) {
                    echo "<tr>";
                    echo "<td><input type='checkbox' class='rowCheckbox'></td>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filter logic
    document.getElementById("testOrderFilter").addEventListener("input", function () {
        filterTable(1, this.value);
    });

    document.getElementById("testNameFilter").addEventListener("input", function () {
        filterTable(2, this.value);
    });

    document.getElementById("sectionFilter").addEventListener("input", function () {
        filterTable(5, this.value);
    });

    function filterTable(colIndex, value) {
        const rows = document.querySelectorAll("#testMasterlist tbody tr");
        const filter = value.toUpperCase();

        rows.forEach(row => {
            const cell = row.cells[colIndex].textContent.toUpperCase();
            row.style.display = cell.includes(filter) ? "" : "none";
        });

        updateRecordCount();
    }

    // Record Count
    function updateRecordCount() {
        const rows = document.querySelectorAll("#testMasterlist tbody tr");
        let visibleCount = 0;
        rows.forEach(row => {
            if (row.style.display !== "none") visibleCount++;
        });
        document.getElementById("recordCount").textContent = "Total Records: " + visibleCount;
    }

    // Initial record count on load
    updateRecordCount();
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Masterlist Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<?php include('sidebar.php'); ?>

<div class="main-content p-4" style="margin-left: 250px;">
    <div class="container-fluid">
        <h2 class="mb-4">Test Masterlist Setup</h2>

        <div class="row mb-3">
            <div class="col-md-12">
                <input type="text" id="unifiedSearch" class="form-control" placeholder="Search by Test Order, Test or Section...">
            </div>
        </div>

        <div class="mb-2">
            <span id="recordCount" class="badge bg-primary">Total Records: 0</span>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("unifiedSearch").addEventListener("input", function() {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll("#testMasterlist tbody tr");
        
        rows.forEach(row => {
            const testOrder = row.cells[1].textContent.toLowerCase();
            const testName = row.cells[2].textContent.toLowerCase();
            const section = row.cells[5].textContent.toLowerCase();
            
            const matches = 
                testOrder.includes(searchText) || 
                testName.includes(searchText) || 
                section.includes(searchText);
                
            row.style.display = matches ? "" : "none";
        });
        
        updateRecordCount();
    });

    function updateRecordCount() {
        const rows = document.querySelectorAll("#testMasterlist tbody tr");
        let visibleCount = 0;
        rows.forEach(row => {
            if (row.style.display !== "none") visibleCount++;
        });
        document.getElementById("recordCount").textContent = "Total Records: " + visibleCount;
    }

    updateRecordCount();
</script>
</body>
</html>
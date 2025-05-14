<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include('sidebar.php'); ?>

<div class="main-content p-4" style="margin-left: 250px;">
    <div class="container-fluid">
        <h2 class="mb-4">Records</h2>

        <!-- Section Dropdown Only -->
        <div class="mb-4">
            <label for="sectionDropdown" class="form-label fw-bold">Select Section</label>
            <select id="sectionDropdown" class="form-select w-50">
                <option selected disabled>Choose a section</option>
                <option value="Chemistry">Chemistry</option>
                <option value="Hematology">Hematology</option>
                <option value="Clinical Microscopy">Clinical Microscopy</option>
                <option value="Serology">Serology</option>
                <option value="Special Serology">Special Serology</option>
                <option value="Immunoassays">Immunoassays</option>
                <option value="Immuno Chemistry">Immuno Chemistry</option>
            </select>
        </div>

        <!-- Placeholder Message -->
        <div class="card">
            <div class="card-body">
                <p>Select a section from the dropdown above to view its records.</p>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

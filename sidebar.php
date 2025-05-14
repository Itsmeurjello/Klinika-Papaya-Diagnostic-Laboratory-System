<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
  .sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background-color: #feb1b7;
    padding-top: 60px;
    overflow-y: auto;
  }

  .sidebar .logo {
    text-align: center;
    margin-bottom: 20px;
  }

  .sidebar .logo img {
    width: 120px;
    height: auto;
    border-radius: 50%;
  }

  .sidebar a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    font-size: 16px;
    color: #ffffff;
    text-decoration: none;
    transition: background 0.3s ease, transform 0.2s ease;
  }

  .sidebar a:hover {
    background-color: #495057;
    transform: translateX(5px);
  }

  .sidebar a.active {
    background-color: #495057;
    color: white;
    transform: translateX(5px);
    border-left: 4px solid #ffffff;
  }

  .sidebar a i {
    margin-right: 10px;
    transition: transform 0.3s ease;
  }

  .sidebar a:hover i, .sidebar a.active i {
    transform: scale(1.2);
  }
</style> 
    
</head>
<body>
   
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<?php
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
  <div class="logo">
    <img src="assets/image/profile.jpg" alt="Logo">
  </div>
  
  <a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
    <i class="fas fa-tachometer-alt"></i> Dashboard
  </a>
  <a href="patientlist.php" class="<?php echo ($currentPage == 'patientlist.php') ? 'active' : ''; ?>">
    <i class="fas fa-user-injured"></i> Patient List Form
  </a>
  <a href="requestlist.php" class="<?php echo ($currentPage == 'requestlist.php') ? 'active' : ''; ?>">
    <i class="fas fa-file-medical"></i> Request List
  </a>
  <a href="pendinglist.php" class="<?php echo ($currentPage == 'pendinglist.php') ? 'active' : ''; ?>">
    <i class="fas fa-hourglass-half"></i> Pending List
  </a>
  <a href="test_summary.php" class="<?php echo ($currentPage == 'test_summary.php') ? 'active' : ''; ?>">
    <i class="fas fa-vials"></i> Test Summary
  </a>
  <a href="records.php" class="<?php echo ($currentPage == 'records.php') ? 'active' : ''; ?>">
    <i class="fas fa-folder-open"></i> Records
  </a>
  <a href="reports.php" class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
    <i class="fas fa-chart-line"></i> Reports
  </a>
  <a href="setup.php" class="<?php echo ($currentPage == 'setup.php') ? 'active' : ''; ?>">
    <i class="fas fa-cogs"></i> Setup
  </a>
</div>

</body>
</html>
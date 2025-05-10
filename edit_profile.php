<?php
session_start();
require_once 'config/db_connect.php';
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: index.php");
    exit;
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if user doesn't exist
if (!$user) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    // If password is provided, hash it; otherwise, keep the current password
    $password = !empty($_POST['password']) ? hash('sha256', $_POST['password']) : $user['password'];

    // Only allow admin to update the role
    $role = ($_SESSION['role'] === 'admin' && isset($_POST['role'])) ? $_POST['role'] : $user['role'];

    // Update user data in database
    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
    $stmt->execute([$username, $password, $role, $userId]);

    $_SESSION['message'] = "Profile updated successfully.";
    header("Location: dashboard.php");
    exit;
}
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>
<body>
    <!-- Header -->
<header class="d-flex justify-content-end align-items-center pe-4">
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
            <li><a class="dropdown-item" href="logout.php" onclick="return confirm('Are you sure you want to log out?')">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a></li>
        </ul>
    </div>
</header>

<div class="container mt-5">
    <h2>Edit Profile</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($user['username']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">New Password (Leave blank to keep current)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <!-- Only show the Role field if the user is an Admin -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>
</body>
</html>

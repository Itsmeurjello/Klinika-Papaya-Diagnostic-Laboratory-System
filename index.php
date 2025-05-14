<!DOCTYPE html> 
<html lang="en">
<head>
    <title>Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            background-color: #006D9C;
            color: white;
            padding: 15px;
            font-weight: bold;
        }
        .login-body {
            padding: 20px;
            background-color: white;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #006D9C;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container rounded">
            <!-- Logo Section -->
            <div class="logo-container">
                <img src="assets/image/profile.jpg" alt="DIAGNOSTIC LABORATORY AND CLINIC" class="img-fluid" style="max-height: 100px;">
                <div class="logo-text">DIAGNOSTIC LABORATORY AND CLINIC</div>
            </div>
            
            <!-- Error Message Display -->
            <?php if(isset($errMsg)): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars($errMsg); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div class="login-body">
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php if(isset($_POST['username'])) echo htmlspecialchars($_POST['username']); ?>" 
                               autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               value="<?php if(isset($_POST['password'])) echo htmlspecialchars($_POST['password']); ?>" 
                               autocomplete="off" required>

                               
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <?php if(isset($_SESSION['login_success'])): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Login Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    You have successfully logged in.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['login_success']); endif; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show the success modal if it's set
        <?php if(isset($_SESSION['login_success'])): ?>
            var myModal = new bootstrap.Modal(document.getElementById('successModal'));
            myModal.show();
        <?php endif; ?>
    </script>
</body>
</html>

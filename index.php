<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow-lg" style="width: 400px;">
            <div class="card-header bg-primary text-white text-center">
                <h4>Library Management System</h4>
            </div>
            <div class="card-body">
                <?php
                if (isset($_GET['error'])) {
                    echo '<div class="alert alert-danger">Invalid username or password</div>';
                }
                ?>
                <form action="admin/login_process.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <div class="mt-3 text-center">
                    <small class="text-muted">Default: admin / admin123</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

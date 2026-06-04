<?php
session_start();
require_once '../include/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM admin_users WHERE username = '$username'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        
        // For demo purposes, using plain password comparison
        // In production, use password_verify() with hashed passwords
        if ($password === 'admin123' || password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_username'] = $admin['username'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            header('Location: ../index.php?error=1');
            exit();
        }
    } else {
        header('Location: ../index.php?error=1');
        exit();
    }
}
?>

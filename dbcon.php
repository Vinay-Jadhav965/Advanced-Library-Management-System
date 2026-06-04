<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'project_library';

// Create connection
$con = mysqli_connect($host, $user, $password, $dbname);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($con, "utf8");
?>

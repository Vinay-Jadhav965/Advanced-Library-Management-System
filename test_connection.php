<?php
// Test database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'project_library';

$con = mysqli_connect($host, $user, $password, $dbname);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "<h2>✅ Database Connection Successful!</h2>";
    
    // Check if tables exist
    $tables = ['admin_users', 'categories', 'books', 'members', 'transactions'];
    echo "<h3>Checking Tables:</h3>";
    
    foreach ($tables as $table) {
        $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ $table table exists<br>";
        } else {
            echo "❌ $table table missing<br>";
        }
    }
    
    // Check if admin user exists
    $result = mysqli_query($con, "SELECT * FROM admin_users WHERE username = 'admin'");
    if (mysqli_num_rows($result) > 0) {
        echo "<br>✅ Admin user exists<br>";
        echo "<strong>Login Details:</strong><br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "<br>❌ Admin user not found<br>";
    }
    
    mysqli_close($con);
    
    echo "<br><h3>Next Steps:</h3>";
    echo "1. If all tables exist, you can <a href='index.php'>Go to Login Page</a><br>";
    echo "2. If tables are missing, import the database.sql file in phpMyAdmin<br>";
    echo "3. Make sure Apache and MySQL are running in XAMPP/WAMP";
}
?>

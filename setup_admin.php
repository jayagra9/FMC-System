<?php
require_once 'config.php';

// Add admin user
$full_name = 'Admin User';
$username = 'admin';
$email = 'admin@fmc.com';
$password = 'admin123'; // Change this to a secure password
$role = 'admin';

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo "Admin user already exists!";
    } else {
        // Hash password and insert admin
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $username, $email, $hashed_password, $role]);
        echo "Admin user created successfully!<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "<br>Please change the password after first login!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
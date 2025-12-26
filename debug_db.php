<?php
require_once 'config.php';

// Check users table structure
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Users Table Structure:</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";

// Get sample user data
$stmt = $pdo->query("SELECT * FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Sample User Data:</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";
?>

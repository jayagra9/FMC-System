<?php
require_once 'config.php';
header('Content-Type: text/plain');
$uid = 1;
$stmt = $pdo->prepare('SELECT id, username, profile_picture FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
var_export($user);
$abs = __DIR__ . DIRECTORY_SEPARATOR . $user['profile_picture'];
echo "\nabs path: $abs\n";
echo "file_exists(abs): " . (file_exists($abs) ? '1' : '0') . "\n";
echo "is_readable: " . (is_readable($abs) ? '1' : '0') . "\n";
echo "web src: " . $user['profile_picture'] . "\n";
?>
<?php
require_once 'config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!isset($_SESSION['user_id'])) {
    echo "No session user. Please log in first.\n";
    exit;
}
$uid = $_SESSION['user_id'];
echo "User ID: $uid\n";
try {
    $stmt = $pdo->prepare('SELECT id, profile_picture FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "No user row found for id $uid\n";
        exit;
    }
    $pp = $row['profile_picture'] ?? null;
    echo "DB profile_picture value: " . ($pp === null ? '[NULL]' : $pp) . "\n";
    if ($pp) {
        $full = __DIR__ . DIRECTORY_SEPARATOR . $pp;
        echo "Full path: $full\n";
        echo "File exists: " . (file_exists($full) ? 'YES' : 'NO') . "\n";
        if (file_exists($full)) {
            echo "File size: " . filesize($full) . " bytes\n";
        }
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}

$log = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'upload_debug.log';
if (file_exists($log)) {
    echo "\n--- Last 200 chars of upload_debug.log ---\n";
    $s = file_get_contents($log);
    echo substr($s, -200);
} else {
    echo "\nNo upload_debug.log file found at: $log\n";
}

// PHP limits
echo "\nPHP upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "PHP post_max_size: " . ini_get('post_max_size') . "\n";

?>
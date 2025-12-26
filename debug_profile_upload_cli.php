<?php
require_once 'config.php';

// CLI debug that does not require session
try {
    $stmt = $pdo->query('SELECT id, username, profile_picture FROM users LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "No users found in DB\n";
        exit;
    }
    $uid = $row['id'];
    echo "Sample user id: $uid\n";
    echo "username: " . ($row['username'] ?? '[none]') . "\n";
    $pp = $row['profile_picture'] ?? null;
    echo "DB profile_picture value: " . ($pp===null? '[NULL]': $pp) . "\n";
    if ($pp) {
        $full = __DIR__ . DIRECTORY_SEPARATOR . $pp;
        echo "Full path: $full\n";
        echo "File exists: " . (file_exists($full) ? 'YES' : 'NO') . "\n";
        if (file_exists($full)) echo "File size: " . filesize($full) . " bytes\n";
    }
    $log = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'upload_debug.log';
    if (file_exists($log)) {
        echo "\n--- upload_debug.log (last 200 chars) ---\n";
        $s = file_get_contents($log);
        echo substr($s, -200) . "\n";
    } else {
        echo "\nNo upload_debug.log found at: $log\n";
    }
    echo "\nPHP upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "PHP post_max_size: " . ini_get('post_max_size') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
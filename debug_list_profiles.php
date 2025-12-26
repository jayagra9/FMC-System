<?php
require_once 'config.php';
header('Content-Type: text/plain');
try {
    $stmt = $pdo->query("SELECT id, username, email, profile_picture FROM users ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "id={$r['id']} username={$r['username']} email={$r['email']} profile_picture={$r['profile_picture']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

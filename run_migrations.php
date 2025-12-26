<?php
/**
 * Simple migration runner for local environments.
 * Usage (CLI):
 *   php run_migrations.php
 * Or open in browser (only recommended on local dev):
 *   http://localhost/fmc_systems/run_migrations.php
 */
require_once __DIR__ . '/config.php';

// Start session only when needed and not already active
if (php_sapi_name() !== 'cli') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    // allow only admin via web
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Forbidden\n";
        exit;
    }
}

$sqlDir = __DIR__ . '/sql';
$files = glob($sqlDir . '/*.sql');
if (!$files) {
    echo "No SQL files found in {$sqlDir}\n";
    exit;
}

foreach ($files as $f) {
    echo "Applying: " . basename($f) . "...\n";
    $sql = file_get_contents($f);
    if ($sql === false) { echo "Failed to read $f\n"; continue; }
    try {
        $pdo->beginTransaction();
        // split by ; on line endings is risky for complex SQL, attempt whole exec first
        $pdo->exec($sql);
        $pdo->commit();
        echo "OK\n";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";

?>

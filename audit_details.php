<?php
require_once __DIR__ . '/config.php';
session_start();

// simple admin guard
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';

$allowed = ['distress_vessels', 'silent_vessels', 'border_crossings', 'profiles', 'payments'];
if (!in_array($table, $allowed, true) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, table_name, record_id, action, changes, changed_by, changed_at, ip_address, user_agent FROM activity_audit WHERE table_name = ? AND record_id = ? ORDER BY changed_at DESC');
    $stmt->execute([$table, $id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'rows' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'message' => $e->getMessage()]);
}

?>

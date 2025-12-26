<?php
// Simple helper to record audit entries into `activity_audit`.
// Usage: require_once 'audit_helpers.php'; record_audit($pdo, 'border_crossings', $id, 'create', $data);
function record_audit(PDO $pdo, string $table, $record_id, string $action, $changes = null) {
    try {
        $changed_by = $_SESSION['user_id'] ?? null;
        $dt = new DateTime('now', new DateTimeZone('Asia/Colombo'));
        $changed_at = $dt->format('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $jsonChanges = null;
        if ($changes !== null) {
            if (is_string($changes)) {
                $jsonChanges = $changes;
            } else {
                $jsonChanges = json_encode($changes, JSON_UNESCAPED_UNICODE);
            }
        }
        $stmt = $pdo->prepare("INSERT INTO activity_audit (table_name, record_id, action, changed_by, changed_at, changes, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$table, $record_id, $action, $changed_by, $changed_at, $jsonChanges, $ip, $ua]);
    } catch (Exception $e) {
        error_log('record_audit failed: ' . $e->getMessage());
    }
}

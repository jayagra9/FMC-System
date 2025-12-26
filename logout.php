<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    try {
        // Log logout activity
        $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
        $logout_time = $sri_lanka_time->format('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, timestamp, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'logout', $logout_time, $ip_address]);
    } catch (Exception $e) {
        // Silent fail - still logout even if logging fails
        error_log("Logout logging error: " . $e->getMessage());
    }
}

session_destroy();
header("Location: login.php");
exit();
?>
<?php
require_once 'config.php';

// Create activity_logs table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Activity logs table created or already exists.<br>";
} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "<br>";
}

// Get all users
try {
    $stmt = $pdo->query("SELECT id, full_name FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        // Clear existing logs
        $pdo->exec("DELETE FROM activity_logs");
        echo "✓ Cleared existing logs.<br>";
        
        // Insert sample logs
        $actions = ['login', 'logout', 'login', 'logout'];
        $ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102', '192.168.1.103'];
        $count = 0;
        
        foreach ($users as $user) {
            foreach ($actions as $idx => $action) {
                $timestamp = date('Y-m-d H:i:s', strtotime("-" . (count($users) * 4 - $idx) . " minutes"));
                $ip = $ips[$idx % count($ips)];
                
                $insert = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, timestamp, ip_address) 
                    VALUES (:user_id, :action, :timestamp, :ip_address)
                ");
                $insert->execute([
                    ':user_id' => $user['id'],
                    ':action' => $action,
                    ':timestamp' => $timestamp,
                    ':ip_address' => $ip
                ]);
                $count++;
            }
        }
        
        echo "✓ Inserted " . $count . " sample activity logs for " . count($users) . " users.<br>";
    } else {
        echo "⚠ No users found in database. Please register users first.<br>";
    }
} catch (Exception $e) {
    echo "✗ Error inserting logs: " . $e->getMessage() . "<br>";
}

// Show current data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM activity_logs");
    $result = $stmt->fetch();
    echo "<br><strong>Total activity logs in database: " . $result['count'] . "</strong><br>";
    
    // Show sample
    $stmt = $pdo->query("SELECT al.*, u.full_name FROM activity_logs al JOIN users u ON al.user_id = u.id ORDER BY al.timestamp DESC LIMIT 5");
    $logs = $stmt->fetchAll();
    if (!empty($logs)) {
        echo "<br><strong>Sample logs:</strong><br>";
        echo "<ul>";
        foreach ($logs as $log) {
            echo "<li>" . htmlspecialchars($log['full_name']) . " - " . strtoupper($log['action']) . " at " . $log['timestamp'] . " from " . $log['ip_address'] . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><br><a href='activitylogs.php'><button style='padding: 10px 20px; background: #1a2b47; color: white; border: none; border-radius: 4px; cursor: pointer;'>Go to Activity Logs</button></a>";
?>

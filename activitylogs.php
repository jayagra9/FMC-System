<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access_denied.php");
    exit();
}

// Fetch all activity logs from database
$logs = [];
try {
    $stmt = $pdo->prepare("
        SELECT al.id, u.full_name, u.username, al.action, al.timestamp, al.ip_address 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        ORDER BY al.timestamp DESC
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching activity logs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - FMC Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #f5f7fa;
        }

        .sidebar {
            width: 250px;
            background: #1a2b47;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar-content {
            flex: 1;
        }

        .sidebar a {
            display: block;
            padding: 14px;
            color: #d9d9d9;
            text-decoration: none;
            font-size: 16px;
        }

        .sidebar a:hover {
            background: #0f1c30;
            color: white;
        }

        .logout-btn {
            display: block;
            padding: 14px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            background: #c1121f;
            text-align: center;
            margin: 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #a00c1a;
        }

        .main {
            margin-left: 260px;
            padding: 20px;
        }

        .main h1 {
            color: #1a2b47;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        table th {
            background: #1a2b47;
            color: white;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        table tr:hover {
            background: #f0f0f0;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-login {
            background: #27ae60;
            color: white;
        }

        .badge-logout {
            background: #e67e22;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error {
            background: #fce4e4;
            color: #c1121f;
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #c1121f;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-content">
        <h2 style="text-align:center;">FMC Admin</h2>

        <a href="admindashboard.php">Dashboard</a>
        <a href="register_user.php">Register User</a>
        <a href="vessels.php">Vessels</a>
        <a href="admin_border_crossings.php">Border Crossing Alerts</a>
        <a href="admin_silent_vessels.php">Silent Vessel Alerts</a>
        <a href="admin_distress_vessels.php">Distress Alerts</a>
        <a href="owners.php">Vessel Owners</a>
        <a href="reports.php">Reports</a>
        <a href="usermanagement.php">User Management</a>
        <a href="activitylogs.php">Activity Logs</a>
        <a href="admin_profile.php">Settings</a>   
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="main">
    <h1><i class="fas fa-history"></i> Activity Logs</h1>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <?php if (empty($logs)): ?>
            <div class="no-data">
                <p><i class="fas fa-inbox"></i> No activity logs found.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> User</th>
                        <th><i class="fas fa-user-circle"></i> Username</th>
                        <th><i class="fas fa-tasks"></i> Action</th>
                        <th><i class="fas fa-clock"></i> Date & Time</th>
                        <th><i class="fas fa-globe"></i> IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['username']); ?></td>
                            <td>
                                <span class="badge <?php echo strtolower($log['action']) === 'login' ? 'badge-login' : 'badge-logout'; ?>">
                                    <?php echo htmlspecialchars(strtoupper($log['action'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['timestamp']))); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
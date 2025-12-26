<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all users with full details
try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, username, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error loading users.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - FMC Admin</title>
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
        .sidebar-content { flex: 1; }
        .sidebar a {
            display: block;
            padding: 14px;
            color: #d9d9d9;
            text-decoration: none;
            font-size: 16px;
        }
        .sidebar a:hover, .sidebar a.active {
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
        .logout-btn:hover { background: #a00c1a; }

        .main {
            margin-left: 250px;
            padding: 30px;
        }
        .main h1 {
            color: #1a2b47;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #1a2b47;
            color: white;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background: #f8fafc;
        }
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-admin {
            background: #ef4444;
            color: white;
        }
        .badge-user {
            background: #2dd4bf;
            color: white;
        }
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
        }
        .error {
            background: #fee;
            color: #c1121f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c1121f;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
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
        <h1>User Management</h1>

        <?php if (isset($error)): ?>
            <div class="error">Error: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($users)): ?>
                <div class="no-data">
                    No users found.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $u): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($u['full_name'] ?? '—') ?></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($u['created_at'])) ?><br>
                                <small style="color:#888;"><?= date('h:i A', strtotime($u['created_at'])) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
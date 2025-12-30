<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');

try {
    $sql = "
        SELECT al.id, u.full_name, u.username, al.action, al.timestamp, al.ip_address 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (
            u.full_name LIKE :search
            OR u.username LIKE :search
            OR al.action LIKE :search
            OR al.ip_address LIKE :search
        )";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY al.timestamp DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching activity logs: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - FMC Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { margin: 0; font-family: Arial; background: #f5f7fa; }
        .main { margin-left: 260px; padding: 30px; }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .topbar h1 {
            color: #1a2b47;
            margin: 0;
        }
        .search-box {
            display: flex;
            gap: 10px;
        }
        .search-box input[type="text"] {
            padding: 10px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .search-box button {
            padding: 10px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        .search-box button:hover {
            background: #0056b3;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #1a2b47;
            color: white;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        td {
            padding: 12px 20px;
            border-bottom: 1px solid #eee;
        }
        tr:hover { background: #f8fafc; }

        .badge-action {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .badge-login { background: #28a745; }
        .badge-logout { background: #dc3545; }

        .error-message {
            background: #fce4e4;
            color: #c1121f;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c1121f;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1><i class="fas fa-history"></i> Activity Logs</h1>
        <form class="search-box" method="GET">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search user, action, IP...">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <?php if (empty($logs)): ?>
            <div style="padding: 20px; text-align:center; color:#666;">No activity logs found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Date & Time</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['full_name']) ?></td>
                            <td><?= htmlspecialchars($log['username']) ?></td>
                            <td>
                                <span class="badge-action <?= strtolower($log['action']) === 'login' ? 'badge-login' : 'badge-logout'; ?>">
                                    <?= htmlspecialchars(strtoupper($log['action'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['timestamp']))) ?></td>
                            <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

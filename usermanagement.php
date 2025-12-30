<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$message = "";
$message_type = "";

// Handle delete form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $deleteId = (int)$_POST['delete_user_id'];

    // Prevent deleting yourself
    if ($deleteId === $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "error";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);

            if ($stmt->rowCount() > 0) {
                $message = "User deleted successfully.";
                $message_type = "success";
            } else {
                $message = "User not found or cannot be deleted.";
                $message_type = "error";
            }
        } catch (Exception $e) {
            $message = "Error deleting user: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Fetch users with search
try {
    $sql = "SELECT id, full_name, username, email, role, created_at FROM users WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (full_name LIKE :search OR username LIKE :search OR email LIKE :search)";
        $params[':search'] = "%$search%";
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error loading users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - FMC Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { margin: 0; font-family: Arial; background: #f5f7fa; }
        .main { margin-left: 260px; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .topbar h1 { color: #1a2b47; margin: 0; }
        .search-box { display: flex; gap: 10px; }
        .search-box input[type="text"] { padding: 10px; width: 250px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .search-box button { padding: 10px 16px; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .search-box button:hover { background: #0056b3; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a2b47; color: white; padding: 16px 20px; text-align: left; font-weight: 600; font-size: 14px; text-transform: uppercase; }
        td { padding: 16px 20px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-admin { background: #ef4444; color: white; }
        .badge-user { background: #2dd4bf; color: white; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-delete:hover { background: #a71d2a; }
        .no-data { text-align: center; padding: 60px 20px; color: #666; font-size: 18px; }
        .message-success { background: #d5f4e6; color: #27ae60; padding: 10px; border-left: 4px solid #27ae60; margin-bottom: 15px; }
        .message-error { background: #fce4e4; color: #c1121f; padding: 10px; border-left: 4px solid #c1121f; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>User Management</h1>
        <form class="search-box" method="GET">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, username, or email">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (!empty($message)): ?>
        <div class="<?php echo $message_type === 'success' ? 'message-success' : 'message-error'; ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <?php if (empty($users)): ?>
            <div class="no-data">No users found.</div>
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
                        <th>Action</th>
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
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
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

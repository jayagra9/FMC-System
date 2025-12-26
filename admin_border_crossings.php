<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

$rows = [];
$error = null;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM border_crossings LIKE 'created_by'")->fetch();
    if ($cols) {
        $sql = "SELECT bc.*, uc.username AS created_by_username, uu.username AS updated_by_username
                FROM border_crossings bc
                LEFT JOIN users uc ON bc.created_by = uc.id
                LEFT JOIN users uu ON bc.updated_by = uu.id
                ORDER BY bc.created_at DESC LIMIT 200";
    } else {
        $sql = "SELECT bc.*, bc.username AS entered_by FROM border_crossings bc ORDER BY bc.created_at DESC LIMIT 200";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error fetching records: ' . $e->getMessage();
}

function fetch_audits($pdo, $id) {
    try {
        $s = $pdo->prepare("SELECT changed_at, action, changed_by, changes FROM activity_audit WHERE table_name = 'border_crossings' AND record_id = ? ORDER BY changed_at DESC LIMIT 10");
        $s->execute([$id]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin — Border Crossing Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { margin:0; font-family:Arial; background:#f5f7fa; }
        .sidebar { width:250px; background:#1a2b47; height:100vh; position:fixed; left:0; top:0; padding-top:20px; color:#fff; display:flex; flex-direction:column; }
        .sidebar a { display:block; padding:14px; color:#d9d9d9; text-decoration:none; font: size 16px; }
        .sidebar a:hover { background:#0f1c30; color:#fff; }
        .logout-btn { display:block; padding:12px; color:#fff; text-decoration:none; background:#c1121f; text-align:center; margin:10px; border-radius:4px; font-weight:bold; }
        .main { margin-left:260px; padding:20px; }
        .main h1 { color:#1a2b47; }

        .card-list { display:flex; flex-direction:column; gap:12px; }
        .item { background:#fff; padding:12px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.06); display:flex; justify-content:space-between; align-items:flex-start; }
        .item .left { display:flex; gap:12px; flex-wrap:wrap; }
        .meta { font-size:13px; color:#0f172a; background:#fbfdff; padding:6px 8px; border-radius:6px; }
        .small { color:#6b7280; font-size:12px; }
        .actions { display:flex; gap:8px; }
        .btn { padding:8px 10px; border-radius:6px; border:1px solid #dbeafe; background:#eaf2ff; color:#1f4bb8; cursor:pointer; text-decoration:none; }
        .history { background:#fff7ed; border-color:#fcd34d; color:#794a02; }
        .no-data { padding:40px; text-align:center; color:#666; background:#fff; border-radius:8px; }
        .error { background:#fce4e4; color:#c1121f; padding:12px; border-radius:4px; margin-bottom:12px; }
        .audit-list { margin-top:8px; padding-top:8px; border-top:1px dashed #eef2ff; font-size:13px; }
        .audit-entry { font-size:13px; color:#374151; margin-bottom:6px; }
    </style>
</head>
<body>
    <div class="sidebar">
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
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="main">
        <h1><i class="fas fa-ship"></i> Border Crossing Records</h1>
        <?php if (!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if (empty($rows)): ?>
            <div class="no-data"><i class="fas fa-inbox"></i> No border crossing records found.</div>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($rows as $r): ?>
                    <div class="item">
                        <div class="left">
                            <div class="meta"><strong>##<?php echo htmlspecialchars($r['id']); ?></strong></div>
                            <div class="meta"><strong>IMO:</strong> <?php echo htmlspecialchars($r['vessel_imo_number'] ?? ''); ?></div>
                            <div class="meta"><strong>EEZ:</strong> <?php echo htmlspecialchars($r['eez'] ?? ''); ?></div>
                            <div class="meta"><strong>Owner:</strong> <?php echo htmlspecialchars($r['phone_number'] ?? ''); ?></div>
                            <div class="meta small">Entered by: <?php echo htmlspecialchars($r['username'] ?? ($r['entered_by'] ?? '')); ?></div>
                            <div class="meta small">Created: <?php echo !empty($r['created_at']) ? htmlspecialchars($r['created_at']) : 'N/A'; ?> by <?php echo htmlspecialchars($r['created_by_username'] ?? ''); ?></div>
                        </div>
                        <div class="actions">
                            <button class="btn history" data-id="<?php echo (int)$r['id']; ?>">History</button>
                        </div>
                    </div>
                    <?php $audits = fetch_audits($pdo, $r['id']); if ($audits): ?>
                        <div class="audit-list">
                            <?php foreach ($audits as $a): ?>
                                <div class="audit-entry"><?php echo htmlspecialchars($a['changed_at']); ?> — <?php echo htmlspecialchars($a['action']); ?> by <?php echo htmlspecialchars($a['changed_by'] ?? ''); ?> — <?php echo htmlspecialchars(substr($a['changes'],0,200)); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

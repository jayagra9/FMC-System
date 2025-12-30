<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

$search = trim($_GET['search'] ?? '');
$rows = [];
$error = null;

try {
    $sql = "
        SELECT s.*, 
               u.username AS created_by_username
        FROM services s
        LEFT JOIN users u ON s.created_by = u.id
        WHERE 1=1
    ";

    if ($search !== '') {
        $sql .= " AND (
            s.iom_zms LIKE :search
            OR s.imul_no LIKE :search
            OR s.bt_sn LIKE :search
            OR s.home_port LIKE :search
            OR s.contact_number LIKE :search
            OR s.current_status LIKE :search
            OR s.feedback_to_call LIKE :search
            OR s.comments LIKE :search
            OR s.installation_checklist LIKE :search
            OR u.username LIKE :search
        )";
    }

    $sql .= " ORDER BY s.created_at DESC LIMIT 200";

    $stmt = $pdo->prepare($sql);
    if ($search !== '') {
        $term = "%$search%";
        $stmt->bindValue(':search', $term);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error fetching records: ' . $e->getMessage();
}

function fetch_audits($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT changed_at, action, changed_by, changes
            FROM activity_audit
            WHERE table_name = 'services'
              AND record_id = ?
            ORDER BY changed_at DESC
            LIMIT 10
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin — Service Logs</title>
    <style>
        body { margin:0; font-family:Arial; background:#f5f7fa; }
        .main { margin-left:260px; padding:20px; }
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
            width: 300px;
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
        .card {
            background:#fff;
            padding:15px;
            margin-bottom:14px;
            border-radius:8px;
            border-left: 4px solid #007bff;
        }
        .meta { font-size:13px; margin-bottom:6px; color: #333; }
        .audit {
            margin-top:10px;
            padding-top:10px;
            border-top:1px dashed #d1d5db;
            font-size:13px;
        }
        .audit-entry {
            margin-bottom:8px;
        }
        .error {
            background:#fee;
            color:#c1121f;
            padding:12px;
            border-radius:6px;
            margin-bottom:12px;
            border-left: 4px solid #c1121f;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>Service Records</h1>
        <form class="search-box" method="GET">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search services...">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <p>No service records found.</p>
    <?php endif; ?>

    <?php foreach ($rows as $r): ?>
        <div class="card">
            <div class="meta"><strong>ID:</strong> <?= htmlspecialchars($r['id']) ?></div>
            <div class="meta"><strong>IOM/ZMS:</strong> <?= htmlspecialchars($r['iom_zms']) ?></div>
            <div class="meta"><strong>IMUL No:</strong> <?= htmlspecialchars($r['imul_no']) ?></div>
            <div class="meta"><strong>BT SN:</strong> <?= htmlspecialchars($r['bt_sn']) ?></div>
            <div class="meta"><strong>Home Port:</strong> <?= htmlspecialchars($r['home_port']) ?></div>
            <div class="meta"><strong>Contact:</strong> <?= htmlspecialchars($r['contact_number']) ?></div>
            <div class="meta"><strong>Created:</strong> <?= htmlspecialchars($r['created_at']) ?> by <?= htmlspecialchars($r['created_by_username']) ?></div>

            <?php $audits = fetch_audits($pdo, $r['id']); ?>
            <?php if ($audits): ?>
                <div class="audit"><strong>Change History:</strong>
                    <?php foreach ($audits as $a): ?>
                        <div class="audit-entry">
                            <?= htmlspecialchars($a['changed_at']) ?> — <?= strtoupper(htmlspecialchars($a['action'])) ?> by <?= htmlspecialchars($a['changed_by'] ?? '') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>

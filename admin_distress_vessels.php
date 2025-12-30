<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');

$rows = [];
$error = null;

try {
    $sql = "
        SELECT dv.*, 
               uc.username AS created_by_username, 
               uu.username AS updated_by_username
        FROM distress_vessels dv
        LEFT JOIN users uc ON dv.created_by = uc.id
        LEFT JOIN users uu ON dv.updated_by = uu.id
        WHERE 1=1
    ";

    if ($search !== '') {
        $sql .= " AND (
            dv.vessel_name LIKE :search
            OR dv.owner_name LIKE :search
            OR dv.contact_number LIKE :search
            OR dv.position LIKE :search
        )";
    }

    $sql .= " ORDER BY dv.created_at DESC LIMIT 200";

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
            WHERE table_name = 'distress_vessels'
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
    <title>Admin — Distress Vessel Alerts</title>
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
        <h1>Distress Vessel Alerts</h1>
        <form class="search-box" method="GET">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search vessel, owner, contact...">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <p>No records found.</p>
    <?php endif; ?>

    <?php foreach ($rows as $r): ?>
        <div class="card">
            <div class="meta"><strong>ID:</strong> <?= htmlspecialchars($r['id']) ?></div>
            <div class="meta"><strong>Vessel:</strong> <?= htmlspecialchars($r['vessel_name'] ?? 'N/A') ?></div>
            <div class="meta"><strong>Owner:</strong> <?= htmlspecialchars($r['owner_name'] ?? 'N/A') ?></div>
            <div class="meta"><strong>Contact:</strong> <?= htmlspecialchars($r['contact_number'] ?? 'N/A') ?></div>
            <div class="meta">
                <strong>Created:</strong> <?= htmlspecialchars($r['created_at']) ?> by <?= htmlspecialchars($r['created_by_username']) ?>
            </div>
            <?php if (!empty($r['updated_at'])): ?>
                <div class="meta">
                    <strong>Updated:</strong> <?= htmlspecialchars($r['updated_at']) ?> by <?= htmlspecialchars($r['updated_by_username']) ?>
                </div>
            <?php endif; ?>

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

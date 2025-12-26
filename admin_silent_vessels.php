<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$per_page = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

try {
    $base_sql = "SELECT sv.*, uc.username AS created_by_username, uu.username AS updated_by_username
                 FROM silent_vessels sv
                 LEFT JOIN users uc ON sv.created_by = uc.id
                 LEFT JOIN users uu ON sv.updated_by = uu.id
                 WHERE 1=1";
    $params = [];

    if ($search_query) {
        $base_sql .= " AND (sv.vessel_name LIKE :s OR sv.owner_name LIKE :s OR sv.relevant_harbour LIKE :s OR sv.remarks LIKE :s OR sv.username LIKE :s)";
        $params[':s'] = "%$search_query%";
    }

    if ($date_from && $date_to) {
        $base_sql .= " AND sv.created_at BETWEEN :df AND :dt";
        $params[':df'] = $date_from . ' 00:00:00';
        $params[':dt'] = $date_to . ' 23:59:59';
    } elseif ($date_from) {
        $base_sql .= " AND sv.created_at >= :df";
        $params[':df'] = $date_from . ' 00:00:00';
    } elseif ($date_to) {
        $base_sql .= " AND sv.created_at <= :dt";
        $params[':dt'] = $date_to . ' 23:59:59';
    }

    $countSql = preg_replace('/SELECT\s+sv\.,.*/s', 'SELECT COUNT(*)', $base_sql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $total_pages = max(1, ceil($total / $per_page));

    $sql = $base_sql . " ORDER BY sv.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit();
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="admin_silent_vessels_' . date('Ymd') . '.xls"');
    echo "<table border=1>";
    $headers = ['ID','Vessel Name','Owner Name','Owner Contact','Relevant Harbour','Owner Info Date','Owner Informed','SMS To Owner','Date To Investigate','Comment','Remarks','Entered By','Created By','Created At','Updated By','Updated At'];
    echo '<tr>';
    foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
    echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($r['id']) . '</td>';
        echo '<td>' . htmlspecialchars($r['vessel_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['owner_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['owner_contact_number'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['relevant_harbour'] ?? '') . '</td>';
        echo '<td>' . (!empty($r['owner_information_date']) ? (new DateTime($r['owner_information_date']))->format('Y-m-d') : '') . '</td>';
        echo '<td>' . htmlspecialchars($r['owner_informed'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['sms_to_owner'] ?? '') . '</td>';
        echo '<td>' . (!empty($r['date_to_investigate']) ? (new DateTime($r['date_to_investigate']))->format('Y-m-d') : '') . '</td>';
        echo '<td>' . htmlspecialchars($r['comment'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['remarks'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['username'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($r['created_by_username'] ?? '') . '</td>';
        echo '<td>' . (!empty($r['created_at']) ? (new DateTime($r['created_at']))->format('Y-m-d H:i:s') : '') . '</td>';
        echo '<td>' . htmlspecialchars($r['updated_by_username'] ?? '') . '</td>';
        echo '<td>' . (!empty($r['updated_at']) ? (new DateTime($r['updated_at']))->format('Y-m-d H:i:s') : '') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Silent Vessel Logs</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    body { margin:0; font-family:Arial, sans-serif; background:#f5f7fa; }
    .sidebar { width:250px; background:#1a2b47; height:100vh; position:fixed; left:0; top:0; padding-top:20px; color:#fff; display:flex; flex-direction:column; }
    .sidebar a { display:block; padding:14px; color:#d9d9d9; text-decoration:none; font-size:15px; }
    .sidebar a:hover { background:#0f1c30; color:#fff; }
    .logout-btn { display:block; padding:12px; color:#fff; text-decoration:none; background:#c1121f; text-align:center; margin:10px; border-radius:4px; font-weight:bold; }
    .main { margin-left:260px; padding:20px; }
    .main h1 { color:#1a2b47; }

    .card-list { display:flex; flex-direction:column; gap:12px; }
    .item { background:#fff; padding:12px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.06); display:flex; justify-content:space-between; align-items:flex-start; }
    .left { display:flex; gap:12px; flex-wrap:wrap; }
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
<?php require_once 'sidebar.php'; ?>
<div class="main">
    <h1><i class="fas fa-volume-off"></i> Silent Vessel Records</h1>
    <?php if (!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="no-data"><i class="fas fa-inbox"></i> No silent vessel records found.</div>
    <?php else: ?>
        <div class="card-list">
            <?php foreach ($rows as $r): ?>
                <div class="item">
                    <div class="left">
                        <div class="meta"><strong>##<?php echo htmlspecialchars($r['id']); ?></strong></div>
                        <div class="meta"><strong>Vessel:</strong> <?php echo htmlspecialchars($r['vessel_name'] ?? ''); ?></div>
                        <div class="meta"><strong>Owner:</strong> <?php echo htmlspecialchars($r['owner_name'] ?? ''); ?></div>
                        <div class="meta"><strong>Contact:</strong> <?php echo htmlspecialchars($r['owner_contact_number'] ?? ''); ?></div>
                        <div class="meta small">Harbour: <?php echo htmlspecialchars($r['relevant_harbour'] ?? ''); ?></div>
                        <div class="meta small">Entered by: <?php echo htmlspecialchars($r['username'] ?? ($r['entered_by'] ?? '')); ?></div>
                        <div class="meta small">Created: <?php echo !empty($r['created_at']) ? htmlspecialchars($r['created_at']) : 'N/A'; ?> by <?php echo htmlspecialchars($r['created_by_username'] ?? ''); ?></div>
                    </div>
                    <div class="actions">
                        <button class="btn history" data-id="<?php echo (int)$r['id']; ?>">History</button>
                    </div>
                </div>
                <?php $audits = (function($pdo, $id){ try{ $s=$pdo->prepare("SELECT changed_at, action, changed_by, changes FROM activity_audit WHERE table_name='silent_vessels' AND record_id=? ORDER BY changed_at DESC LIMIT 10"); $s->execute([$id]); return $s->fetchAll(PDO::FETCH_ASSOC);}catch(Exception$e){return[];} })($pdo, $r['id']); if ($audits): ?>
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
<div id="history-modal" style="display:none; position:fixed; inset:0; background:rgba(2,6,23,0.6); align-items:center; justify-content:center; z-index:9999;">
    <div style="width:90%; max-width:900px; background:#fff; border-radius:8px; padding:18px; max-height:80vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="margin:0;">Audit History</h3>
            <button onclick="closeHistory()" class="btn">Close</button>
        </div>
        <div id="history-contents">Loading…</div>
    </div>
</div>
<script>
function closeHistory(){ document.getElementById('history-modal').style.display='none'; }
async function showFullHistory(e, table, id){
    if(e) e.preventDefault();
    const modal = document.getElementById('history-modal');
    const contents = document.getElementById('history-contents');
    modal.style.display = 'flex';
    contents.innerHTML = 'Loading...';
    try{
        const res = await fetch('audit_details.php?table='+encodeURIComponent(table)+'&id='+encodeURIComponent(id));
        const data = await res.json();
        if(!data.ok){ contents.innerHTML = '<div style="color:#c1121f;">Error loading history</div>'; return; }
        if(!data.rows.length){ contents.innerHTML = '<div>No audit entries found.</div>'; return; }
        let html = '<div style="display:flex;flex-direction:column;gap:10px;">';
        data.rows.forEach(r=>{
            let changes = r.changes || '';
            try{ changes = JSON.stringify(JSON.parse(changes), null, 2); }catch(e){ /* keep raw */ }
            html += `<div style="background:#f8fafc;padding:10px;border-radius:6px;"><div style="font-size:13px;color:#374151;margin-bottom:6px;"><strong>${r.changed_at}</strong> — ${r.action} by ${r.changed_by || ''}</div><pre style="white-space:pre-wrap;background:#fff;padding:10px;border-radius:4px;border:1px solid #eef2ff;overflow:auto;max-height:280px;">${escapeHtml(changes)}</pre></div>`;
        });
        html += '</div>';
        contents.innerHTML = html;
    }catch(err){ contents.innerHTML = '<div style="color:#c1121f;">Network error</div>'; }
}
function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
document.querySelectorAll('.history').forEach(b=>b.addEventListener('click', function(){ const id=this.dataset.id; showFullHistory(null,'silent_vessels', id); }));
</script>
</body>
</html>
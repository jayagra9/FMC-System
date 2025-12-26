<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$services = [];

try {
    $sql = "SELECT * FROM services WHERE 1=1";
    $params = [];

    if ($search_query) {
        $sql .= " AND (
            iom_zms LIKE :search OR 
            imul_no LIKE :search OR 
            bt_sn LIKE :search OR 
            home_port LIKE :search OR 
            contact_number LIKE :search OR 
            current_status LIKE :search OR 
            feedback_to_call LIKE :search OR 
            comments LIKE :search OR 
            installation_checklist LIKE :search
        )";
        $params[':search'] = "%$search_query%";
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "Error fetching data: " . $e->getMessage();
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="services_' . date('Ymd') . '.xls"');

    echo '<table border="1">
        <tr>
            <th>Record No</th>
            <th>IOM/ZMS</th>
            <th>IMUL No</th>
            <th>BT SN</th>
            <th>Installed Date</th>
            <th>Home Port</th>
            <th>Contact Number</th>
            <th>Current</th>
            <th>Feedback to Call</th>
            <th>Service Done Date</th>
            <th>Comments</th>
            <th>Installation Checklist</th>
        </tr>';

    foreach ($services as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['iom_zms']) . '</td>';
        echo '<td>' . htmlspecialchars($row['imul_no']) . '</td>';
        echo '<td>' . htmlspecialchars($row['bt_sn']) . '</td>';
        echo '<td>' . htmlspecialchars($row['installed_date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['home_port']) . '</td>';
        echo '<td>' . htmlspecialchars($row['contact_number']) . '</td>';
        echo '<td>' . htmlspecialchars($row['current_status']) . '</td>';
        echo '<td>' . htmlspecialchars($row['feedback_to_call']) . '</td>';
        echo '<td>' . htmlspecialchars($row['service_done_date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['comments']) . '</td>';
        echo '<td>' . htmlspecialchars($row['installation_checklist']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Services - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
</head>

<body>
<?php require_once 'nav.php'; ?>

<div class="container">
    <div class="card">
        <h1>SERVICES</h1>

        <div class="controls-container">
            <div class="search-container">
                <label>Search:</label>
                <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary" onclick="searchTable()">Search</button>
            </div>

            <div class="action-buttons-container">
                <a href="add_service.php" class="btn btn-primary">Add New</a>
                <a href="?export=excel&search=<?php echo htmlspecialchars($search_query); ?>" class="btn btn-secondary">Export to Excel</a>
            </div>
        </div>

        <?php if ($services): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Record No</th>
                            <th>IOM/ZMS</th>
                            <th>IMUL No</th>
                            <th>BT SN</th>
                            <th>Installed Date</th>
                            <th>Home Port</th>
                            <th>Contact Number</th>
                            <th>Current</th>
                            <th>Feedback to Call</th>
                            <th>Service Done Date</th>
                            <th>Comments</th>
                            <th>Installation Checklist</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['iom_zms']) ?></td>
                                <td><?= htmlspecialchars($row['imul_no']) ?></td>
                                <td><?= htmlspecialchars($row['bt_sn']) ?></td>
                                <td><?= htmlspecialchars($row['installed_date']) ?></td>
                                <td><?= htmlspecialchars($row['home_port']) ?></td>
                                <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                <td><?= htmlspecialchars($row['current_status']) ?></td>
                                <td><?= htmlspecialchars($row['feedback_to_call']) ?></td>
                                <td><?= htmlspecialchars($row['service_done_date']) ?></td>
                                <td><?= htmlspecialchars($row['comments']) ?></td>
                                <td><?= htmlspecialchars($row['installation_checklist']) ?></td>
                                <td>
                                    <a href="edit_service.php?id=<?= $row['id'] ?>" class="btn btn-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No service records found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function searchTable() {
    const search = document.getElementById('searchInput').value;
    window.location.href = '?search=' + encodeURIComponent(search);
}
</script>

</body>
</html>

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
    echo "Error fetching data: " . htmlspecialchars($e->getMessage());
    exit();
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
        echo '<tr>
            <td>'.$row['id'].'</td>
            <td>'.$row['iom_zms'].'</td>
            <td>'.$row['imul_no'].'</td>
            <td>'.$row['bt_sn'].'</td>
            <td>'.$row['installed_date'].'</td>
            <td>'.$row['home_port'].'</td>
            <td>'.$row['contact_number'].'</td>
            <td>'.$row['current_status'].'</td>
            <td>'.$row['feedback_to_call'].'</td>
            <td>'.$row['service_done_date'].'</td>
            <td>'.$row['comments'].'</td>
            <td>'.$row['installation_checklist'].'</td>
        </tr>';
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

<style>
.table-container {
    overflow-x: auto;
    width: 100%;
}
.data-table {
    width: 100%;
    min-width: 1500px;
    border-collapse: collapse;
    background-color: azure;
}
.data-table th, .data-table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #000;
    white-space: nowrap;
    vertical-align: top;
}
.data-table th {
    background-color: #3363e7ff;
    color: #000;
    font-weight: 600;
    font-family: Arial, Helvetica, sans-serif;
}
h1 {
    text-align: center;
    font-size: 2em;
    margin-bottom: 20px;
}
.btn {
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
}
.btn-primary {
    background-color: #2DD4BF;
    color: white;
    min-width: 200px;
    text-align: center;
}
.btn-secondary {
    background-color: #1E3A8A;
    color: white;
}
.controls-container {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}
.search-container,
.action-buttons-container,
.export-container {
    display: flex;
    gap: 10px;
    width: 100%;
}
.search-container input {
    padding: 8px;
    border: 1px solid #D1D5DB;
    border-radius: 4px;
}
.export-container select {
    padding: 8px;
    border-radius: 4px;
}
</style>
</head>

<body>
<?php require_once 'nav.php'; ?>

<div class="container">
<div class="card">
<h1>SERVICES</h1>

<div class="controls-container">

<div class="search-container">
<label>Search:</label>
<input type="text" id="searchInput" value="<?= htmlspecialchars($search_query) ?>">
<button class="btn btn-primary" onclick="searchTable()">Search</button>
</div>

<div class="action-buttons-container" style="justify-content:flex-end;">
<a href="add_service.php" class="btn btn-primary">Add New</a>
</div>

<div class="export-container">
<select id="exportType">
<option value="">Select Export Type</option>
<option value="excel">Excel</option>
</select>
<button onclick="exportTable()" class="btn btn-secondary">Export</button>
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
<td><?= $row['id'] ?></td>
<td><?= $row['iom_zms'] ?></td>
<td><?= $row['imul_no'] ?></td>
<td><?= $row['bt_sn'] ?></td>
<td><?= $row['installed_date'] ?></td>
<td><?= $row['home_port'] ?></td>
<td><?= $row['contact_number'] ?></td>
<td><?= $row['current_status'] ?></td>
<td><?= $row['feedback_to_call'] ?></td>
<td><?= $row['service_done_date'] ?></td>
<td><?= $row['comments'] ?></td>
<td><?= $row['installation_checklist'] ?></td>
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
function exportTable() {
    if (document.getElementById('exportType').value === 'excel') {
        window.location.href = '?export=excel&search=' + encodeURIComponent(document.getElementById('searchInput').value);
    }
}
</script>

</body>
</html>

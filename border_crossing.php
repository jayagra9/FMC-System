<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Pagination settings
$per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Handle search and filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_field = isset($_GET['date_field']) ? trim($_GET['date_field']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$first_notice = isset($_GET['first_notice']) ? trim($_GET['first_notice']) : '';
$called_owner_status = isset($_GET['called_owner_status']) ? trim($_GET['called_owner_status']) : '';
$border_crossings = [];

try {
    // Base query
    $sql = "SELECT id, vessel_imo_number, eez, owner_informed_datetime, phone_number, first_notice, 
                   after_72hr_boat_status, date_of_investigation, called_owner_to_inform_dc, 
                   test_message_correct, departure_date, after_72hr_remark, remarks 
            FROM border_crossings WHERE 1=1";
    $count_sql = "SELECT COUNT(*) FROM border_crossings WHERE 1=1";
    $params = [];

    // Text search
    if ($search_query) {
        $sql .= " AND (vessel_imo_number LIKE ? OR eez LIKE ? OR first_notice LIKE ? OR remarks LIKE ?)";
        $count_sql .= " AND (vessel_imo_number LIKE ? OR eez LIKE ? OR first_notice LIKE ? OR remarks LIKE ?)";
        $params = array_merge($params, ["%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%"]);
    }

    // Date filter
    if ($date_field && ($date_from || $date_to)) {
        if ($date_field === 'called_owner_date') {
            $sql .= " AND SUBSTRING_INDEX(called_owner_to_inform_dc, '|', 1)";
            $count_sql .= " AND SUBSTRING_INDEX(called_owner_to_inform_dc, '|', 1)";
        } elseif ($date_field === 'text_message_date') {
            $sql .= " AND SUBSTRING_INDEX(test_message_correct, '|', 1)";
            $count_sql .= " AND SUBSTRING_INDEX(test_message_correct, '|', 1)";
        } else {
            $sql .= " AND $date_field";
            $count_sql .= " AND $date_field";
        }
        if ($date_from && $date_to) {
            $sql .= " BETWEEN ? AND ?";
            $count_sql .= " BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        } elseif ($date_from) {
            $sql .= " >= ?";
            $count_sql .= " >= ?";
            $params[] = $date_from;
        } elseif ($date_to) {
            $sql .= " <= ?";
            $count_sql .= " <= ?";
            $params[] = $date_to;
        }
    }

    // First Notice filter
    if ($first_notice) {
        $sql .= " AND first_notice = ?";
        $count_sql .= " AND first_notice = ?";
        $params[] = $first_notice;
    }

    // Called Owner Status filter
    if ($called_owner_status) {
        $sql .= " AND SUBSTRING_INDEX(called_owner_to_inform_dc, '|', -1) = ?";
        $count_sql .= " AND SUBSTRING_INDEX(called_owner_to_inform_dc, '|', -1) = ?";
        $params[] = $called_owner_status;
    }

    // Get total records for pagination
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Add pagination
    $sql .= " ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $border_crossings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error fetching data: " . htmlspecialchars($e->getMessage());
    exit();
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="border_crossings_' . date('Ymd') . '.xls"');
    echo '<table border="1"><tr><th>ID</th><th>Vessel IMO Number</th><th>EEZ</th><th>Owner Informed Date</th><th>Phone Number</th><th>First Notice</th><th>Still Inside</th><th>Sent to Investigation</th><th>Date of Submission</th><th>Called Owner Date</th><th>Called Owner Phone</th><th>Called Owner Status</th><th>Text Message Date</th><th>Text Message</th><th>Departure Cancel Date</th><th>After 72 Hour Remark</th><th>Remarks</th></tr>';
    foreach ($border_crossings as $crossing) {
        $after_72hr = explode('|', $crossing['after_72hr_boat_status'] ?? '');
        $called_owner = explode('|', $crossing['called_owner_to_inform_dc'] ?? '');
        $text_message = explode('|', $crossing['test_message_correct'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($crossing['id']) . '</td>';
        echo '<td>' . htmlspecialchars($crossing['vessel_imo_number']) . '</td>';
        echo '<td>' . htmlspecialchars($crossing['eez']) . '</td>';
        echo '<td>' . (!empty($crossing['owner_informed_datetime']) ? (new DateTime($crossing['owner_informed_datetime']))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($crossing['phone_number']) . '</td>';
        echo '<td>' . htmlspecialchars($crossing['first_notice'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($after_72hr[0] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($after_72hr[1] ?? 'N/A') . '</td>';
        echo '<td>' . (!empty($crossing['date_of_investigation']) ? (new DateTime($crossing['date_of_investigation']))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . (!empty($called_owner[0]) ? (new DateTime($called_owner[0]))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($called_owner[1] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($called_owner[2] ?? 'N/A') . '</td>';
        echo '<td>' . (!empty($text_message[0]) ? (new DateTime($text_message[0]))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($text_message[1] ?? 'N/A') . '</td>';
        echo '<td>' . (!empty($crossing['departure_date']) ? (new DateTime($crossing['departure_date']))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($crossing['after_72hr_remark'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($crossing['remarks'] ?? 'N/A') . '</td>';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Border Crossing - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            border: 1px solid #000000ff;
            white-space: nowrap;
            vertical-align: top;
        }
        .data-table th {
            background-color: #3363e7ff;
            color: #000000ff;
            font-weight: 600;
            font-family: Arial, Helvetica, sans-serif;
        }
        .sub-header {
            background-color: #000000ff;
            color: #ffffffff;
            font-weight: bold;
        }
        h1 {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #2DD4BF;
            color: white;
            min-width: 200px;
            text-align: center;
            display: inline-block;
        }
        .btn-secondary {
            background-color: #1E3A8A;
            color: white;
        }
        .controls-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-bottom: 20px;
            gap: 10px;
        }
        .search-container, .date-filter-container, .status-filter-container, .action-buttons-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            width: 100%;
        }
        .search-container input, .date-filter-container select, .date-filter-container input, .status-filter-container select {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
        }
        .date-filter-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .date-filter-container label, .status-filter-container label {
            margin-right: 5px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }
        .first-notice-owner { background-color: #6adda2ff; }
        .first-notice-habour { background-color: #fee2e2; }
        .first-notice-wrong { background-color: #fef9c3; }
        .first-notice-noresponse { background-color: #e0e7ff; }
        .first-notice-notworking { background-color: #f3e8ff; }
        .export-container {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: flex-end;
        }
        .export-container select {
            padding: 8px;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
            background-color: white;
            font-size: 14px;
            min-width: 150px;
        }
        .export-container select:focus {
            outline: none;
            border-color: #2DD4BF;
            box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.3);
        }
        .export-container button {
            background-color: #1E3A8A;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .export-container button:hover {
            background-color: #1E40AF;
        }
    </style>
</head>
<body>
    <?php require_once 'nav.php'; ?>
    <div class="container">
        <div class="card">
            <h1>BORDER CROSSINGS</h1>
            <div class="controls-container">
                <div class="search-container">
                    <label for="searchInput">Search:</label>
                    <input type="text" id="searchInput" placeholder="Search by Vessel, EEZ, etc..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button class="btn btn-primary" onclick="searchTable()">Search</button>
                </div>
                <div class="date-filter-container">
                    <label for="dateField">Date Field:</label>
                    <select id="dateField" name="date_field">
                        <option value="" <?php echo $date_field === '' ? 'selected' : ''; ?>>Select Date Field</option>
                        <option value="owner_informed_datetime" <?php echo $date_field === 'owner_informed_datetime' ? 'selected' : ''; ?>>Owner Informed Date</option>
                        <option value="date_of_investigation" <?php echo $date_field === 'date_of_investigation' ? 'selected' : ''; ?>>Date of Submission</option>
                        <option value="called_owner_date" <?php echo $date_field === 'called_owner_date' ? 'selected' : ''; ?>>Called Owner Date</option>
                        <option value="text_message_date" <?php echo $date_field === 'text_message_date' ? 'selected' : ''; ?>>Text Message Date</option>
                        <option value="departure_date" <?php echo $date_field === 'departure_date' ? 'selected' : ''; ?>>Departure Cancel Date</option>
                    </select>
                    <div class="date-filter-group">
                        <label for="dateFrom">From:</label>
                        <input type="date" id="dateFrom" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="date-filter-group">
                        <label for="dateTo">To:</label>
                        <input type="date" id="dateTo" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <button class="btn btn-primary" onclick="searchTable()">Filter</button>
                </div>
                <div class="status-filter-container">
                    <label for="firstNotice">First Notice:</label>
                    <select id="firstNotice" name="first_notice">
                        <option value="" <?php echo $first_notice === '' ? 'selected' : ''; ?>>Select First Notice</option>
                        <option value="Owner Informed" <?php echo $first_notice === 'Owner Informed' ? 'selected' : ''; ?>>Owner Informed</option>
                        <option value="Inform to habour" <?php echo $first_notice === 'Inform to habour' ? 'selected' : ''; ?>>Inform to habour</option>
                        <option value="Wrong number" <?php echo $first_notice === 'Wrong number' ? 'selected' : ''; ?>>Wrong number</option>
                        <option value="Did not respond" <?php echo $first_notice === 'Did not respond' ? 'selected' : ''; ?>>Did not respond</option>
                        <option value="Not working" <?php echo $first_notice === 'Not working' ? 'selected' : ''; ?>>Not working</option>
                    </select>
                    <label for="calledOwnerStatus">Called Owner Status:</label>
                    <select id="calledOwnerStatus" name="called_owner_status">
                        <option value="" <?php echo $called_owner_status === '' ? 'selected' : ''; ?>>Select Called Owner Status</option>
                        <option value="Reached" <?php echo $called_owner_status === 'Reached' ? 'selected' : ''; ?>>Reached</option>
                        <option value="Not Reached" <?php echo $called_owner_status === 'Not Reached' ? 'selected' : ''; ?>>Not Reached</option>
                        <option value="Busy" <?php echo $called_owner_status === 'Busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="No Response" <?php echo $called_owner_status === 'No Response' ? 'selected' : ''; ?>>No Response</option>
                    </select>
                    <button class="btn btn-primary" onclick="clearFilters()">Remove Filters</button>
                </div>
                <div class="action-buttons-container" style="justify-content: flex-end; margin-bottom: 20px;">
                    <a href="add_border_crossing.php" class="btn btn-primary" style="width:200px; text-align:center;">Add New</a>
                </div>
                <div class="export-container">
                    <label for="exportType" class="font-medium">Export As:</label>
                    <select id="exportType" name="export_type">
                        <option value="">Select Export Type</option>
                        <option value="excel">Excel</option>
                        <option value="pdf">PDF</option>
                        <option value="image">Image</option>
                    </select>
                    <button onclick="exportTable()">Export</button>
                </div>
            </div>
            <?php if ($border_crossings): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vessel IMO Number</th>
                                <th>EEZ</th>
                                <th colspan="2">Owner Informed Date (1st Time)</th>
                                <th>First Notice</th>
                                <th colspan="3">After 72 Hour Period Boat Status</th>
                                <th colspan="3">Called the Owner to Inform DC</th>
                                <th colspan="2">Text Message (to Owner)</th>
                                <th>Departure Cancel Date</th>
                                <th>After 72 Hour Remark</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th class="sub-header">Date</th>
                                <th class="sub-header">Phone Number</th>
                                <th></th>
                                <th class="sub-header">Still Inside</th>
                                <th class="sub-header">Sent to Investigation</th>
                                <th class="sub-header">Date of Submission</th>
                                <th class="sub-header">Date</th>
                                <th class="sub-header">Phone Number</th>
                                <th class="sub-header">Status</th>
                                <th class="sub-header">Date</th>
                                <th class="sub-header">Message</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($border_crossings as $crossing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($crossing['id']); ?></td>
                                    <td><?php echo htmlspecialchars($crossing['vessel_imo_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($crossing['eez'] ?? 'N/A'); ?></td>
                                    <td><?php echo !empty($crossing['owner_informed_datetime']) ? (new DateTime($crossing['owner_informed_datetime']))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <td><?php echo htmlspecialchars($crossing['phone_number'] ?? 'N/A'); ?></td>
                                    <?php
                                    $first_notice_value = $crossing['first_notice'] ?? '';
                                    $cell_class = '';
                                    if ($first_notice_value === 'Owner Informed') $cell_class = 'first-notice-owner';
                                    elseif ($first_notice_value === 'Inform to habour') $cell_class = 'first-notice-habour';
                                    elseif ($first_notice_value === 'Wrong number') $cell_class = 'first-notice-wrong';
                                    elseif ($first_notice_value === 'Did not respond') $cell_class = 'first-notice-noresponse';
                                    elseif ($first_notice_value === 'Not working') $cell_class = 'first-notice-notworking';
                                    ?>
                                    <td class="<?php echo $cell_class; ?>"><?php echo htmlspecialchars($first_notice_value); ?></td>
                                    <?php
                                    $after_72hr = explode('|', $crossing['after_72hr_boat_status'] ?? '');
                                    $still_inside = $after_72hr[0] ?? 'N/A';
                                    $sent_to_investigation = $after_72hr[1] ?? 'N/A';
                                    ?>
                                    <td><?php echo htmlspecialchars($still_inside); ?></td>
                                    <td><?php echo htmlspecialchars($sent_to_investigation); ?></td>
                                    <td><?php echo !empty($crossing['date_of_investigation']) ? (new DateTime($crossing['date_of_investigation']))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <?php
                                    $called_owner = explode('|', $crossing['called_owner_to_inform_dc'] ?? '');
                                    $called_owner_date = $called_owner[0] ?? '';
                                    $called_owner_phone = $called_owner[1] ?? 'N/A';
                                    $called_owner_status_value = $called_owner[2] ?? 'N/A';
                                    ?>
                                    <td><?php echo !empty($called_owner_date) ? (new DateTime($called_owner_date))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <td><?php echo htmlspecialchars($called_owner_phone); ?></td>
                                    <td><?php echo htmlspecialchars($called_owner_status_value); ?></td>
                                    <?php
                                    $text_message = explode('|', $crossing['test_message_correct'] ?? '');
                                    $text_message_date = $text_message[0] ?? '';
                                    $text_message_content = $text_message[1] ?? 'N/A';
                                    ?>
                                    <td><?php echo !empty($text_message_date) ? (new DateTime($text_message_date))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <td><?php echo htmlspecialchars($text_message_content); ?></td>
                                    <td><?php echo !empty($crossing['departure_date']) ? (new DateTime($crossing['departure_date']))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <td><?php echo htmlspecialchars($crossing['after_72hr_remark'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($crossing['remarks'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="edit_border_crossing.php?id=<?php echo htmlspecialchars($crossing['id']); ?>" class="btn btn-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search_query); ?>&date_field=<?php echo urlencode($date_field); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&first_notice=<?php echo urlencode($first_notice); ?>&called_owner_status=<?php echo urlencode($called_owner_status); ?>" class="btn btn-secondary">&laquo;</a>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&date_field=<?php echo urlencode($date_field); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&first_notice=<?php echo urlencode($first_notice); ?>&called_owner_status=<?php echo urlencode($called_owner_status); ?>" class="btn btn-secondary">&lt;</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= min($total_pages, 20); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&date_field=<?php echo urlencode($date_field); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&first_notice=<?php echo urlencode($first_notice); ?>&called_owner_status=<?php echo urlencode($called_owner_status); ?>" class="btn <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-300'; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&date_field=<?php echo urlencode($date_field); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&first_notice=<?php echo urlencode($first_notice); ?>&called_owner_status=<?php echo urlencode($called_owner_status); ?>" class="btn btn-secondary">&gt;</a>
                        <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search_query); ?>&date_field=<?php echo urlencode($date_field); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&first_notice=<?php echo urlencode($first_notice); ?>&called_owner_status=<?php echo urlencode($called_owner_status); ?>" class="btn btn-secondary">&raquo;</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No border crossing records found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function searchTable() {
            const searchValue = document.getElementById('searchInput').value;
            const dateField = document.getElementById('dateField').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const firstNotice = document.getElementById('firstNotice').value;
            const calledOwnerStatus = document.getElementById('calledOwnerStatus').value;
            let url = '?search=' + encodeURIComponent(searchValue);
            if (dateField) {
                url += '&date_field=' + encodeURIComponent(dateField);
                if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
                if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
            }
            if (firstNotice) {
                url += '&first_notice=' + encodeURIComponent(firstNotice);
            }
            if (calledOwnerStatus) {
                url += '&called_owner_status=' + encodeURIComponent(calledOwnerStatus);
            }
            window.location.href = url;
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('dateField').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('firstNotice').value = '';
            document.getElementById('calledOwnerStatus').value = '';
            window.location.href = 'border_crossing.php';
        }

        function exportTable() {
            const exportType = document.getElementById('exportType').value;
            if (!exportType) {
                alert('Please select an export type.');
                return;
            }
            if (exportType === 'excel') {
                const searchValue = document.getElementById('searchInput').value;
                const dateField = document.getElementById('dateField').value;
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                const firstNotice = document.getElementById('firstNotice').value;
                const calledOwnerStatus = document.getElementById('calledOwnerStatus').value;
                let url = '?export=' + encodeURIComponent(exportType) + '&search=' + encodeURIComponent(searchValue);
                if (dateField) {
                    url += '&date_field=' + encodeURIComponent(dateField);
                    if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
                    if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
                }
                if (firstNotice) {
                    url += '&first_notice=' + encodeURIComponent(firstNotice);
                }
                if (calledOwnerStatus) {
                    url += '&called_owner_status=' + encodeURIComponent(calledOwnerStatus);
                }
                window.location.href = url;
            } else if (exportType === 'pdf') {
                const { jsPDF } = window.jspdf;
                html2canvas(document.querySelector('.data-table'), { scale: 1 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({
                        orientation: 'landscape',
                        unit: 'mm',
                        format: 'a4'
                    });
                    const imgWidth = 280;
                    const pageHeight = 210;
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    let heightLeft = imgHeight;
                    let position = 0;

                    pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft >= 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }

                    pdf.save('border_crossings_' + new Date().toISOString().slice(0, 10) + '.pdf');
                });
            } else if (exportType === 'image') {
                html2canvas(document.querySelector('.data-table'), { scale: 2 }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'border_crossings_' + new Date().toISOString().slice(0, 10) + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            }
        }

        document.getElementById('searchInput').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });

        document.getElementById('dateFrom').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });

        document.getElementById('dateTo').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });

        document.getElementById('firstNotice').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });

        document.getElementById('calledOwnerStatus').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });
    </script>
</body>
</html>
<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle search and filters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_field = isset($_GET['date_field']) ? trim($_GET['date_field']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$owner_informed = isset($_GET['owner_informed']) ? trim($_GET['owner_informed']) : '';
$sms_to_owner = isset($_GET['sms_to_owner']) ? trim($_GET['sms_to_owner']) : '';
$silent_vessels = [];

try {
    $sql = "SELECT * FROM silent_vessels WHERE 1=1";
    $params = [];

    // Text search
    if ($search_query) {
        $sql .= " AND (vessel_name LIKE :search OR owner_name LIKE :search OR relevant_harbour LIKE :search OR owner_informed LIKE :search OR sms_to_owner LIKE :search OR username LIKE :search OR remarks LIKE :search)";
        $params[':search'] = "%$search_query%";
    }

    // Date filter
    if ($date_field && ($date_from || $date_to)) {
        $sql .= " AND $date_field";
        if ($date_from && $date_to) {
            $sql .= " BETWEEN :date_from AND :date_to";
            $params[':date_from'] = $date_from;
            $params[':date_to'] = $date_to;
        } elseif ($date_from) {
            $sql .= " >= :date_from";
            $params[':date_from'] = $date_from;
        } elseif ($date_to) {
            $sql .= " <= :date_to";
            $params[':date_to'] = $date_to;
        }
    }

    // Owner Informed filter
    if ($owner_informed) {
        $sql .= " AND owner_informed = :owner_informed";
        $params[':owner_informed'] = $owner_informed;
    }

    // SMS to Owner filter
    if ($sms_to_owner) {
        $sql .= " AND sms_to_owner = :sms_to_owner";
        $params[':sms_to_owner'] = $sms_to_owner;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $silent_vessels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error fetching data: " . $e->getMessage();
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="silent_vessels_' . date('Ymd') . '.xls"');
    echo '<table border="1"><tr><th>ID</th><th>Vessel Name</th><th>Owner Name</th><th>Owner Contact Number</th><th>Relevant Harbour</th><th>Owner Information Date</th><th>Owner Informed</th><th>SMS to Owner</th><th>Date to Investigate</th><th>Comment</th><th>Remarks</th><th>Username</th></tr>';
    foreach ($silent_vessels as $vessel) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($vessel['id']) . '</td>';
        echo '<td>' . htmlspecialchars($vessel['vessel_name']) . '</td>';
        echo '<td>' . htmlspecialchars($vessel['owner_name'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['owner_contact_number'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['relevant_harbour'] ?? 'N/A') . '</td>';
        echo '<td>' . (!empty($vessel['owner_information_date']) ? (new DateTime($vessel['owner_information_date']))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['owner_informed'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['sms_to_owner'] ?? 'N/A') . '</td>';
        echo '<td>' . (!empty($vessel['date_to_investigate']) ? (new DateTime($vessel['date_to_investigate']))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['comment'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['remarks'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['username'] ?? 'N/A') . '</td>';
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
    <title>Silent Vessel - FMC Fisheries</title>
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
        .first-notice-owner { background-color: #6adda2ff; }      /* Light green */
        .first-notice-habour { background-color: #fee2e2; }     /* Light red */
        .first-notice-wrong { background-color: #fef9c3; }      /* Light yellow */
        .first-notice-noresponse { background-color: #e0e7ff; } /* Light blue */
        .first-notice-notworking { background-color: #f3e8ff; } /* Light purple */
    </style>
</head>
<body>
    <?php require_once 'nav.php'; ?>
    <div class="container">
        <div class="card">
            <h1>PAYMENTS</h1>
            <div class="controls-container">
                <div class="search-container">
                    <label for="searchInput">Search:</label>
                    <input type="text" id="searchInput" placeholder="Search by Vessel, Owner, etc..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button class="btn btn-primary" onclick="searchTable()">Search</button>
                </div>
                <div class="date-filter-container">
                    <label for="dateField">Date Field:</label>
                    <select id="dateField" name="date_field">
                        <option value="" <?php echo $date_field === '' ? 'selected' : ''; ?>>Select Date Field</option>
                        <option value="owner_information_date" <?php echo $date_field === 'owner_information_date' ? 'selected' : ''; ?>>Owner Information Date</option>
                        <option value="date_to_investigate" <?php echo $date_field === 'date_to_investigate' ? 'selected' : ''; ?>>Date to Investigate</option>
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
                    <label for="ownerInformed">Owner Informed:</label>
                    <select id="ownerInformed" name="owner_informed">
                        <option value="" <?php echo $owner_informed === '' ? 'selected' : ''; ?>>Select Owner Informed</option>
                        <option value="Yes" <?php echo $owner_informed === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="No" <?php echo $owner_informed === 'No' ? 'selected' : ''; ?>>No</option>
                        <option value="Not Contacted" <?php echo $owner_informed === 'Not Contacted' ? 'selected' : ''; ?>>Not Contacted</option>
                    </select>
                    <label for="smsToOwner">SMS to Owner:</label>
                    <select id="smsToOwner" name="sms_to_owner">
                        <option value="" <?php echo $sms_to_owner === '' ? 'selected' : ''; ?>>Select SMS Status</option>
                        <option value="Sent" <?php echo $sms_to_owner === 'Sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="Not Sent" <?php echo $sms_to_owner === 'Not Sent' ? 'selected' : ''; ?>>Not Sent</option>
                        <option value="Failed" <?php echo $sms_to_owner === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                    <button class="btn btn-primary" onclick="clearFilters()">Clear Filters</button>
                    <button class="btn btn-primary" onclick="clearFilters()">Remove Filters</button>
                </div>
                <div class="action-buttons-container">
                    <a href="add_silent_vessel.php" class="btn btn-primary">Add New</a>
                    <a href="?export=excel&search=<?php echo htmlspecialchars($search_query); ?>&date_field=<?php echo htmlspecialchars($date_field); ?>&date_from=<?php echo htmlspecialchars($date_from); ?>&date_to=<?php echo htmlspecialchars($date_to); ?>&owner_informed=<?php echo htmlspecialchars($owner_informed); ?>&sms_to_owner=<?php echo htmlspecialchars($sms_to_owner); ?>" class="btn btn-secondary">Export to Excel</a>
                </div>
            </div>
            <?php if ($silent_vessels): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vessel Name</th>
                                <th>Owner Name</th>
                                <th>Owner Contact Number</th>
                                <th>Relevant Harbour</th>
                                <th>Owner Information Date</th>
                                <th>Owner Informed</th>
                                <th>SMS to Owner</th>
                                <th>Date to Investigate</th>
                                <th>Comment</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($silent_vessels as $vessel): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vessel['id']); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['vessel_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['owner_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['owner_contact_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['relevant_harbour'] ?? 'N/A'); ?></td>
                                    <td><?php echo !empty($vessel['owner_information_date']) ? (new DateTime($vessel['owner_information_date']))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <td><?php echo htmlspecialchars($vessel['owner_informed'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['sms_to_owner'] ?? 'N/A'); ?></td>
                                    <td><?php echo !empty($vessel['date_to_investigate']) ? (new DateTime($vessel['date_to_investigate']))->format('Y-m-d') : '0000-00-00'; ?></td>
                                    <td><?php echo htmlspecialchars($vessel['comment'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['remarks'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="edit_silent_vessel.php?id=<?php echo htmlspecialchars($vessel['id']); ?>" class="btn btn-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No silent vessel records found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function searchTable() {
            const searchValue = document.getElementById('searchInput').value;
            const dateField = document.getElementById('dateField').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const ownerInformed = document.getElementById('ownerInformed').value;
            const smsToOwner = document.getElementById('smsToOwner').value;
            let url = '?search=' + encodeURIComponent(searchValue);
            if (dateField) {
                url += '&date_field=' + encodeURIComponent(dateField);
                if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
                if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
            }
            if (ownerInformed) {
                url += '&owner_informed=' + encodeURIComponent(ownerInformed);
            }
            if (smsToOwner) {
                url += '&sms_to_owner=' + encodeURIComponent(smsToOwner);
            }
            window.location.href = url;
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('dateField').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('ownerInformed').value = '';
            document.getElementById('smsToOwner').value = '';
            window.location.href = 'silent_vessel.php';
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

        document.getElementById('ownerInformed').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });

        document.getElementById('smsToOwner').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });
    </script>
</body>
</html>
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
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$distress_vessels = [];

try {
    $sql = "SELECT * FROM distress_vessels WHERE 1=1";
    $params = [];

    // Text search
    if ($search_query) {
        $sql .= " AND (vessel_name LIKE :search OR owner_name LIKE :search OR address LIKE :search OR status LIKE :search OR reason LIKE :search OR notes LIKE :search OR remark LIKE :search)";
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

    // Status filter
    if ($status) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $distress_vessels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error fetching data: " . $e->getMessage();
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="distress_vessels_' . date('Ymd') . '.xls"');
    echo '<table border="1"><tr><th>ID</th><th>Date</th><th>Vessel Name</th><th>Owner\'s Name</th><th>Contact Number</th><th>Address</th><th>Status</th><th>Speed</th><th>Position</th><th>Date and Time of Detection</th><th>Distance from Last Position (to harbour)</th><th>Notes</th><th>Remark</th><th>Departure Form</th><th>Voyage</th><th>Reason</th><th>Username</th></tr>';
    foreach ($distress_vessels as $vessel) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($vessel['id']) . '</td>';
        echo '<td>' . (!empty($vessel['date']) ? (new DateTime($vessel['date']))->format('Y-m-d') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['vessel_name']) . '</td>';
        echo '<td>' . htmlspecialchars($vessel['owner_name'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['contact_number'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['address'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['status'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['speed'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['position'] ?? 'N/A') . '</td>';
        echo '<td>' . (!empty($vessel['date_time_detection']) ? (new DateTime($vessel['date_time_detection']))->format('Y-m-d H:i:s') : 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['distance_last_position'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['notes'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['remark'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['departure_form'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['voyage'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($vessel['reason'] ?? 'N/A') . '</td>';
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
    <title>Distress Vessel - FMC Fisheries</title>
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
            text-align: center;
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
            width: 200px;
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
        img {
            max-width: 100px;
            height: auto;
        }
        .action-buttons-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
        }
        .action-buttons-container > div {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }
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
            <h1>DISTRESS VESSELS</h1>
            <div class="action-buttons-container" style="justify-content: flex-end; margin-bottom: 20px;">
                <a href="add_distress_vessel.php" class="btn btn-primary">Add New</a>
            </div>
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
                        <option value="date" <?php echo $date_field === 'date' ? 'selected' : ''; ?>>Date</option>
                        <option value="date_time_detection" <?php echo $date_field === 'date_time_detection' ? 'selected' : ''; ?>>Date and Time of Detection</option>
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
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>Select Status</option>
                        <option value="In Distress" <?php echo $status === 'In Distress' ? 'selected' : ''; ?>>In Distress</option>
                        <option value="Resolved" <?php echo $status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="Under Investigation" <?php echo $status === 'Under Investigation' ? 'selected' : ''; ?>>Under Investigation</option>
                        <option value="No Response" <?php echo $status === 'No Response' ? 'selected' : ''; ?>>No Response</option>
                    </select>
                    <button class="btn btn-primary" onclick="clearFilters()">Clear Filters</button>
                    <button class="btn btn-primary" onclick="clearFilters()">Remove Filters</button>
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
                <!-- New Generate Report Button -->
                <div class="action-buttons-container">
                    <a href="distress_vessel_report.php?search=<?php echo urlencode($search_query); ?>&date_field=<?php echo urlencode($date_field); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status=<?php echo urlencode($status); ?>" class="btn btn-primary">Generate Report</a>
                </div>
            </div>
            <?php if ($distress_vessels): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th colspan="5">Vessel Details</th>
                                <th colspan="5">Vessel Status</th>
                                <th colspan="2">Comments</th>
                                <th>Departure Form</th>
                                <th>Voyage</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th class="sub-header">Date</th>
                                <th class="sub-header">Name</th>
                                <th class="sub-header">Owner's Name</th>
                                <th class="sub-header">Contact Number</th>
                                <th class="sub-header">Address</th>
                                <th class="sub-header">Status</th>
                                <th class="sub-header">Speed</th>
                                <th class="sub-header">Position</th>
                                <th class="sub-header">Date and Time of Detection</th>
                                <th class="sub-header">Distance from Last Position (to harbour)</th>
                                <th class="sub-header">Notes</th>
                                <th class="sub-header">Remark</th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distress_vessels as $vessel): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vessel['id']); ?></td>
                                    <td><?php echo !empty($vessel['date']) ? (new DateTime($vessel['date']))->format('Y-m-d') : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($vessel['vessel_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['owner_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['contact_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['address'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['status'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['speed'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['position'] ?? 'N/A'); ?></td>
                                    <td><?php echo !empty($vessel['date_time_detection']) ? (new DateTime($vessel['date_time_detection']))->format('Y-m-d H:i:s') : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($vessel['distance_last_position'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['notes'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($vessel['remark'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (!empty($vessel['departure_form'])): ?>
                                            <img src="<?php echo htmlspecialchars($vessel['departure_form']); ?>" alt="Departure Form" width="100">
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($vessel['voyage'])): ?>
                                            <img src="<?php echo htmlspecialchars($vessel['voyage']); ?>" alt="Voyage" width="100">
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($vessel['reason'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="edit_distress_vessel.php?id=<?php echo htmlspecialchars($vessel['id']); ?>" class="btn btn-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No distress vessel records found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function searchTable() {
            const searchValue = document.getElementById('searchInput').value;
            const dateField = document.getElementById('dateField').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const status = document.getElementById('status').value;
            let url = '?search=' + encodeURIComponent(searchValue);
            if (dateField) {
                url += '&date_field=' + encodeURIComponent(dateField);
                if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
                if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
            }
            if (status) {
                url += '&status=' + encodeURIComponent(status);
            }
            window.location.href = url;
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('dateField').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('status').value = '';
            window.location.href = 'distress_vessel.php';
        }

        function exportTable() {
            const type = document.getElementById('exportType').value;
            const params = [
                'search=' + encodeURIComponent(document.getElementById('searchInput').value),
                'date_field=' + encodeURIComponent(document.getElementById('dateField').value),
                'date_from=' + encodeURIComponent(document.getElementById('dateFrom').value),
                'date_to=' + encodeURIComponent(document.getElementById('dateTo').value),
                'status=' + encodeURIComponent(document.getElementById('status').value)
            ].join('&');

            if (!type) {
                alert('Please select an export type.');
                return;
            }
            if (type === 'excel') {
                window.location.href = 'distress_vessel.php?export=excel&' + params;
            } else if (type === 'pdf') {
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

                    pdf.save('distress_vessels_' + new Date().toISOString().slice(0, 10) + '.pdf');
                });
            } else if (type === 'image') {
                html2canvas(document.querySelector('.data-table'), { scale: 2 }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'distress_vessels_' + new Date().toISOString().slice(0, 10) + '.png';
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

        document.getElementById('status').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchTable();
            }
        });
    </script>
</body>
</html>
<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle filters from URL
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_field = isset($_GET['date_field']) ? trim($_GET['date_field']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$distress_vessels = [];

try {
    $sql = "SELECT * FROM distress_vessels WHERE 1=1";
    $params = [];

    if ($search_query) {
        $sql .= " AND (vessel_name LIKE :search OR owner_name LIKE :search OR address LIKE :search OR status LIKE :search OR reason LIKE :search OR notes LIKE :search OR remark LIKE :search)";
        $params[':search'] = "%$search_query%";
    }

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
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distress Vessel Report Form - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            width: 210mm;
            min-height: 297mm;
            box-sizing: border-box;
            display: block;
            margin: 0 auto;
            font-size: 12pt;
        }
        .container {
            width: 190mm; /* Adjusted for 10mm margins */
            margin: 10mm auto;
            padding: 0;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 10mm;
        }
        .header h2 {
            margin: 0;
            font-size: 16pt;
        }
        .header p {
            margin: 0;
            text-align: right;
            font-size: 10pt;
        }
        .vessel-section {
            margin-bottom: 10mm;
            page-break-inside: avoid;
        }
        .form-group {
            margin-bottom: 5mm;
        }
        .form-group label {
            display: inline-block;
            width: 70mm;
            font-weight: bold;
            vertical-align: top;
            margin-right: 5mm;
        }
        .form-group input, .form-group textarea {
            display: inline-block;
            width: 110mm;
            padding: 2mm;
            border: 1px solid #000;
            border-radius: 2mm;
            box-sizing: border-box;
            vertical-align: top;
            font-size: 10pt;
        }
        textarea {
            min-height: 15mm;
            resize: vertical;
        }
        button {
            background-color: #2DD4BF;
            color: white;
            padding: 3mm 6mm;
            border: none;
            border-radius: 2mm;
            cursor: pointer;
            font-size: 10pt;
        }
        button:hover {
            background-color: #1E3A8A;
        }
        hr {
            border: 1px solid #000;
            margin-top: 10mm;
        }

        @media print {
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
            }
            .container {
                margin: 0;
                width: 190mm;
            }
            .vessel-section {
                border: none;
                padding: 0;
            }
            button {
                display: none;
            }
            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>යාත්‍රාවේ තොරතුරු ලබාදීම</h2>
            <p>2025/08/26 03:09 PM (+0530)</p>
        </div>

        <?php if (!empty($distress_vessels)): ?>
            <?php foreach ($distress_vessels as $vessel): ?>
                <div class="vessel-section">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="vessel_id_<?php echo $vessel['id']; ?>">යාත්‍රාවේ නම්බරය :</label>
                            <input type="text" id="vessel_id_<?php echo $vessel['id']; ?>" name="vessel_id_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['id'] ?? ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="vessel_name_<?php echo $vessel['id']; ?>">යාත්‍රාවේ නම :</label>
                            <input type="text" id="vessel_name_<?php echo $vessel['id']; ?>" name="vessel_name_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['vessel_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="owner_name_<?php echo $vessel['id']; ?>">යාත්‍රාවේ අයිතිකරුගේ නම :</label>
                            <input type="text" id="owner_name_<?php echo $vessel['id']; ?>" name="owner_name_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['owner_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact_number_<?php echo $vessel['id']; ?>">යාත්‍රාවේ අයිතිකරුගේ දුරකථන අංකය :</label>
                            <input type="text" id="contact_number_<?php echo $vessel['id']; ?>" name="contact_number_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['contact_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="harbour_<?php echo $vessel['id']; ?>">හාබර් :</label>
                            <input type="text" id="harbour_<?php echo $vessel['id']; ?>" name="harbour_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['harbour'] ?? ''); ?>" placeholder="Enter harbour manually">
                        </div>
                        <div class="form-group">
                            <label for="address_<?php echo $vessel['id']; ?>">ලිපිනය :</label>
                            <textarea id="address_<?php echo $vessel['id']; ?>" name="address_<?php echo $vessel['id']; ?>"><?php echo htmlspecialchars($vessel['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="position_<?php echo $vessel['id']; ?>">යාත්‍රාවේ ස්ථානය (POSITION) :</label>
                            <input type="text" id="position_<?php echo $vessel['id']; ?>" name="position_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['position'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status_<?php echo $vessel['id']; ?>">යාත්‍රාවේ ස්ථිතිය (STATUS) :</label>
                            <input type="text" id="status_<?php echo $vessel['id']; ?>" name="status_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['status'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="speed_<?php echo $vessel['id']; ?>">යාත්‍රාවේ වේගය :</label>
                            <input type="text" id="speed_<?php echo $vessel['id']; ?>" name="speed_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['speed'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_time_detection_<?php echo $vessel['id']; ?>">යාත්‍රාව සොයාගත් වේලාව :</label>
                            <input type="text" id="date_time_detection_<?php echo $vessel['id']; ?>" name="date_time_detection_<?php echo $vessel['id']; ?>" value="<?php echo !empty($vessel['date_time_detection']) ? (new DateTime($vessel['date_time_detection']))->format('Y.m.d H.i.s') : ''; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="distance_last_position_<?php echo $vessel['id']; ?>">හාබර් සිට දුර :</label>
                            <input type="text" id="distance_last_position_<?php echo $vessel['id']; ?>" name="distance_last_position_<?php echo $vessel['id']; ?>" value="<?php echo htmlspecialchars($vessel['distance_last_position'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="notes_<?php echo $vessel['id']; ?>">යාත්‍රාවේ තොරතුරු :</label>
                            <textarea id="notes_<?php echo $vessel['id']; ?>" name="notes_<?php echo $vessel['id']; ?>"><?php echo htmlspecialchars($vessel['notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit">Submit</button>
                        </div>
                    </form>
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No distress vessel records found based on the filters.</p>
        <?php endif; ?>
    </div>
</body>
</html>
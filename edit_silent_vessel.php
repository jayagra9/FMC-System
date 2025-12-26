<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vessel = [];
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM silent_vessels WHERE id = ?");
    $stmt->execute([$id]);
    $vessel = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $data = [
        'id' => $id,
        'vessel_name' => filter_input(INPUT_POST, 'vessel_name', FILTER_SANITIZE_STRING),
        'owner_name' => filter_input(INPUT_POST, 'owner_name', FILTER_SANITIZE_STRING),
        'owner_contact_number' => filter_input(INPUT_POST, 'owner_contact_number', FILTER_SANITIZE_STRING),
        'relevant_harbour' => filter_input(INPUT_POST, 'relevant_harbour', FILTER_SANITIZE_STRING),
        'owner_information_date' => filter_input(INPUT_POST, 'owner_information_date', FILTER_SANITIZE_STRING),
        'owner_informed' => filter_input(INPUT_POST, 'owner_informed', FILTER_SANITIZE_STRING),
        'sms_to_owner' => filter_input(INPUT_POST, 'sms_to_owner', FILTER_SANITIZE_STRING),
        'date_to_investigate' => filter_input(INPUT_POST, 'date_to_investigate', FILTER_SANITIZE_STRING),
        'comment' => filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING),
        'remarks' => filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING)
    ];

    // Append 00:00:00 to date fields
    $data['owner_information_date'] = $data['owner_information_date'] ? $data['owner_information_date'] . ' 00:00:00' : null;
    $data['date_to_investigate'] = $data['date_to_investigate'] ? $data['date_to_investigate'] . ' 00:00:00' : null;

    // current user and timestamp (Asia/Colombo) for update audit
    $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
    $today_date = $sri_lanka_time->format('Y-m-d H:i:s');

    // Log for debugging
    error_log("Received owner_informed: " . $data['owner_informed']);
    error_log("Received sms_to_owner: " . $data['sms_to_owner']);
    error_log("Formatted Dates: owner_information_date={$data['owner_information_date']}, date_to_investigate={$data['date_to_investigate']}");

    if (empty($data['vessel_name'])) {
        echo "<script>alert('Unsuccessful: Vessel Name is required.'); window.location.href = 'edit_silent_vessel.php?id=" . htmlspecialchars($id) . "';</script>";
        exit();
    }

    try {
        // capture old row snapshot
        $oldRow = $vessel ?: [];

        $stmt = $pdo->prepare("UPDATE silent_vessels SET vessel_name = ?, owner_name = ?, owner_contact_number = ?, relevant_harbour = ?, owner_information_date = ?, owner_informed = ?, sms_to_owner = ?, date_to_investigate = ?, comment = ?, remarks = ?, updated_by = ?, updated_at = ? WHERE id = ?");
        $stmt->execute([
            $data['vessel_name'],
            $data['owner_name'],
            $data['owner_contact_number'],
            $data['relevant_harbour'],
            $data['owner_information_date'],
            $data['owner_informed'],
            $data['sms_to_owner'],
            $data['date_to_investigate'],
            $data['comment'],
            $data['remarks'],
            $_SESSION['user_id'],
            $today_date,
            $data['id']
        ]);

        // build new row snapshot
        $newRow = [
            'vessel_name' => $data['vessel_name'],
            'owner_name' => $data['owner_name'],
            'owner_contact_number' => $data['owner_contact_number'],
            'relevant_harbour' => $data['relevant_harbour'],
            'owner_information_date' => $data['owner_information_date'],
            'owner_informed' => $data['owner_informed'],
            'sms_to_owner' => $data['sms_to_owner'],
            'date_to_investigate' => $data['date_to_investigate'],
            'comment' => $data['comment'],
            'remarks' => $data['remarks'],
            'updated_by' => $_SESSION['user_id'],
            'updated_at' => $today_date
        ];

        // compute simple diff
        $diff = [];
        $tracked = array_keys($newRow);
        foreach ($tracked as $col) {
            $oldVal = array_key_exists($col, $oldRow) ? $oldRow[$col] : null;
            $newVal = $newRow[$col];
            if ((string)$oldVal !== (string)$newVal) {
                $diff[$col] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        // record centralized audit (non-fatal)
        try {
            require_once __DIR__ . '/audit_helpers.php';
            record_audit($pdo, 'silent_vessels', $id, 'update', $diff);
        } catch (Exception $ae) {
            error_log('audit record failed (silent update): ' . $ae->getMessage());
        }

        echo "<script>alert('Changes saved successfully!'); window.location.href = 'silent_vessel.php';</script>";
        exit();
    } catch (Exception $e) {
        $msg = addslashes($e->getMessage());
        echo "<script>alert('Unsuccessful: $msg'); window.location.href = 'edit_silent_vessel.php?id=" . htmlspecialchars($id) . "';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Silent Vessel - FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            padding: 10px;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #2DD4BF;
            color: #FFFFFF;
        }
        .btn-primary:hover {
            background-color: #0D9488;
        }
        .error-message {
            color: red;
            margin-top: 10px;
            display: none;
        }
        fieldset {
            border: 1px solid #D1D5DB;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        legend {
            font-weight: 600;
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <?php require_once 'nav.php'; ?>
    <div class="container">
        <div class="card">
            <?php if ($vessel): ?>
                <h1>Edit Silent Vessel</h1>
                <form method="POST" action="edit_silent_vessel.php?id=<?php echo htmlspecialchars($id); ?>" class="form-grid" id="silentVesselForm">
                    <fieldset>
                        <legend>Vessel Details</legend>
                        <div class="form-group">
                            <label for="vessel_name">Vessel Name *</label>
                            <input type="text" name="vessel_name" id="vessel_name" value="<?php echo htmlspecialchars($vessel['vessel_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="owner_name">Owner Name</label>
                            <input type="text" name="owner_name" id="owner_name" value="<?php echo htmlspecialchars($vessel['owner_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="owner_contact_number">Owner Contact Number</label>
                            <input type="text" name="owner_contact_number" id="owner_contact_number" value="<?php echo htmlspecialchars($vessel['owner_contact_number'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="relevant_harbour">Relevant Harbour</label>
                            <input type="text" name="relevant_harbour" id="relevant_harbour" value="<?php echo htmlspecialchars($vessel['relevant_harbour'] ?? ''); ?>">
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Owner Information</legend>
                        <div class="form-group">
                            <label for="owner_information_date">Owner Information Date</label>
                            <input type="date" name="owner_information_date" id="owner_information_date" value="<?php echo !empty($vessel['owner_information_date']) ? (new DateTime($vessel['owner_information_date']))->format('Y-m-d') : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="owner_informed">Owner Informed</label>
                            <select name="owner_informed" id="owner_informed">
                                <option value="">Select</option>
                                <option value="Yes" <?php echo ($vessel['owner_informed'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo ($vessel['owner_informed'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sms_to_owner">SMS to Owner</label>
                            <select name="sms_to_owner" id="sms_to_owner">
                                <option value="">Select</option>
                                <option value="Yes" <?php echo ($vessel['sms_to_owner'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo ($vessel['sms_to_owner'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Investigation</legend>
                        <div class="form-group">
                            <label for="date_to_investigate">Date to Investigate</label>
                            <input type="date" name="date_to_investigate" id="date_to_investigate" value="<?php echo !empty($vessel['date_to_investigate']) ? (new DateTime($vessel['date_to_investigate']))->format('Y-m-d') : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="comment">Comment</label>
                            <input type="text" name="comment" id="comment" value="<?php echo htmlspecialchars($vessel['comment'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <input type="text" name="remarks" id="remarks" value="<?php echo htmlspecialchars($vessel['remarks'] ?? ''); ?>">
                        </div>
                    </fieldset>

                    <div class="form-group full-width">
                        <button type="submit" name="save" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    </div>
                </form>
                <div class="error-message" id="phoneError"></div>
            <?php else: ?>
                <p>Record not found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.getElementById('silentVesselForm').addEventListener('submit', function(event) {
            const ownerPhone = document.getElementById('owner_contact_number').value;
            const phoneError = document.getElementById('phoneError');

            if (ownerPhone && !/^\d{10}$/.test(ownerPhone)) {
                event.preventDefault();
                phoneError.textContent = 'Owner Contact Number must be exactly 10 digits.';
                phoneError.style.display = 'block';
                return;
            }
            phoneError.style.display = 'none';
        });
    </script>
</body>
</html>
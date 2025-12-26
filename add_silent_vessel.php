<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $data = [
        'vessel_name' => filter_input(INPUT_POST, 'vessel_name', FILTER_SANITIZE_STRING),
        'owner_name' => filter_input(INPUT_POST, 'owner_name', FILTER_SANITIZE_STRING),
        'owner_contact_number' => filter_input(INPUT_POST, 'owner_contact_number', FILTER_SANITIZE_STRING),
        'relevant_harbour' => filter_input(INPUT_POST, 'relevant_harbour', FILTER_SANITIZE_STRING),
        'owner_information_date' => filter_input(INPUT_POST, 'owner_information_date', FILTER_SANITIZE_STRING),
        'owner_informed' => filter_input(INPUT_POST, 'owner_informed', FILTER_SANITIZE_STRING),
        'sms_to_owner' => filter_input(INPUT_POST, 'sms_to_owner', FILTER_SANITIZE_STRING),
        'date_to_investigate' => filter_input(INPUT_POST, 'date_to_investigate', FILTER_SANITIZE_STRING),
        'comment' => filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING),
        'remarks' => filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING),
        'username' => $_SESSION['username']
    ];

        // current user and timestamp (Asia/Colombo)
        $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
        $today_date = $sri_lanka_time->format('Y-m-d H:i:s');

    // Append 00:00:00 to date fields
    $data['owner_information_date'] = $data['owner_information_date'] ? $data['owner_information_date'] . ' 00:00:00' : null;
    $data['date_to_investigate'] = $data['date_to_investigate'] ? $data['date_to_investigate'] . ' 00:00:00' : null;

    // Log for debugging
    error_log("Received owner_informed: " . $data['owner_informed']);
    error_log("Received sms_to_owner: " . $data['sms_to_owner']);
    error_log("Formatted Dates: owner_information_date={$data['owner_information_date']}, date_to_investigate={$data['date_to_investigate']}");

    if (empty($data['vessel_name'])) {
        echo "<script>alert('Unsuccessful: Vessel Name is required.'); window.location.href = 'add_silent_vessel.php';</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO silent_vessels (vessel_name, owner_name, owner_contact_number, relevant_harbour, owner_information_date, owner_informed, sms_to_owner, date_to_investigate, comment, remarks, username, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
            $data['username'],
            $_SESSION['user_id'],
            $today_date
        ]);
        // record centralized audit (non-fatal)
        try {
            require_once __DIR__ . '/audit_helpers.php';
            $newId = $pdo->lastInsertId();
            record_audit($pdo, 'silent_vessels', $newId, 'create', $data);
        } catch (Exception $ae) {
            error_log('audit record failed (silent create): ' . $ae->getMessage());
        }
        echo "<script>alert('Successfully added!'); window.location.href = 'silent_vessel.php';</script>";
        exit();
    } catch (Exception $e) {
        $msg = addslashes($e->getMessage());
        echo "<script>alert('Unsuccessful: $msg'); window.location.href = 'add_silent_vessel.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Silent Vessel - FMC Fisheries</title>
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
            <h1>Add New Silent Vessel</h1>
            <form method="POST" action="add_silent_vessel.php" class="form-grid" id="silentVesselForm">
                <fieldset>
                    <legend>Vessel Details</legend>
                    <div class="form-group">
                        <label for="vessel_name">Vessel Name *</label>
                        <input type="text" name="vessel_name" id="vessel_name" required>
                    </div>
                    <div class="form-group">
                        <label for="owner_name">Owner Name</label>
                        <input type="text" name="owner_name" id="owner_name">
                    </div>
                    <div class="form-group">
                        <label for="owner_contact_number">Owner Contact Number</label>
                        <input type="text" name="owner_contact_number" id="owner_contact_number">
                    </div>
                    <div class="form-group">
                        <label for="relevant_harbour">Relevant Harbour</label>
                        <input type="text" name="relevant_harbour" id="relevant_harbour">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Owner Information</legend>
                    <div class="form-group">
                        <label for="owner_information_date">Owner Information Date</label>
                        <input type="date" name="owner_information_date" id="owner_information_date">
                    </div>
                    <div class="form-group">
                        <label for="owner_informed">Owner Informed</label>
                        <select name="owner_informed" id="owner_informed">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sms_to_owner">SMS to Owner</label>
                        <select name="sms_to_owner" id="sms_to_owner">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Investigation</legend>
                    <div class="form-group">
                        <label for="date_to_investigate">Date to Investigate</label>
                        <input type="date" name="date_to_investigate" id="date_to_investigate">
                    </div>
                    <div class="form-group">
                        <label for="comment">Comment</label>
                        <input type="text" name="comment" id="comment">
                    </div>
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <input type="text" name="remarks" id="remarks">
                    </div>
                </fieldset>

                <div class="form-group full-width">
                    <button type="submit" name="save" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
            <div class="error-message" id="phoneError"></div>
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
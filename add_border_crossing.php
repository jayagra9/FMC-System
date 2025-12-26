<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$today_date = $sri_lanka_time->format('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Fetch and sanitize inputs
    $vessel_imo_number = filter_input(INPUT_POST, 'vessel_imo_number', FILTER_SANITIZE_STRING);
    $eez = filter_input(INPUT_POST, 'eez', FILTER_SANITIZE_STRING);
    $owner_informed_datetime = filter_input(INPUT_POST, 'owner_informed_date', FILTER_SANITIZE_STRING);
    $phone_number = filter_input(INPUT_POST, 'owner_informed_phone', FILTER_SANITIZE_STRING);
    $first_notice = filter_input(INPUT_POST, 'first_notice_final', FILTER_SANITIZE_STRING);
    $still_inside = filter_input(INPUT_POST, 'still_inside', FILTER_SANITIZE_STRING);
    $sent_to_investigation = filter_input(INPUT_POST, 'sent_to_investigation', FILTER_SANITIZE_STRING);
    $date_of_investigation = filter_input(INPUT_POST, 'date_of_submission', FILTER_SANITIZE_STRING);
    $called_owner_date = filter_input(INPUT_POST, 'called_owner_date', FILTER_SANITIZE_STRING);
    $called_owner_phone = filter_input(INPUT_POST, 'called_owner_phone', FILTER_SANITIZE_STRING);
    $called_owner_status = filter_input(INPUT_POST, 'called_owner_status_final', FILTER_SANITIZE_STRING);
    $text_message_date = filter_input(INPUT_POST, 'text_message_date', FILTER_SANITIZE_STRING);
    $text_message_content = filter_input(INPUT_POST, 'text_message_content', FILTER_SANITIZE_STRING);
    $departure_date = filter_input(INPUT_POST, 'departure_cancel_date', FILTER_SANITIZE_STRING);
    $after_72hr_remark = filter_input(INPUT_POST, 'after_72hr_remark', FILTER_SANITIZE_STRING);
    $remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING);
    $username = $_SESSION['username'];

    // Format dates to include '00:00:00' for DATETIME columns
    $owner_informed_datetime = $owner_informed_datetime ? $owner_informed_datetime . ' 00:00:00' : null;
    $date_of_investigation = $date_of_investigation ? $date_of_investigation . ' 00:00:00' : null;
    $called_owner_date = $called_owner_date ? $called_owner_date . ' 00:00:00' : null;
    $text_message_date = $text_message_date ? $text_message_date . ' 00:00:00' : null;
    $departure_date = $departure_date ? $departure_date . ' 00:00:00' : null;

    // Log date values for debugging
    error_log("Formatted Dates: owner_informed_datetime=$owner_informed_datetime, date_of_investigation=$date_of_investigation, called_owner_date=$called_owner_date, text_message_date=$text_message_date, departure_date=$departure_date");

    // Concatenated fields
    $after_72hr_boat_status = $still_inside . '|' . $sent_to_investigation;
    $called_owner_to_inform_dc = ($called_owner_date ?: '') . '|' . ($called_owner_phone ?: '') . '|' . ($called_owner_status ?: '');
    $test_message_correct = ($text_message_date ?: '') . '|' . ($text_message_content ?: '');

    if (empty($vessel_imo_number) || empty($eez)) {
        echo "<script>alert('Unsuccessful: Vessel IMO Number and EEZ are required fields.'); window.location.href = 'add_border_crossing.php';</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO border_crossings (vessel_imo_number, eez, owner_informed_datetime, phone_number, first_notice, after_72hr_boat_status, date_of_investigation, called_owner_to_inform_dc, test_message_correct, departure_date, after_72hr_remark, remarks, username, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $vessel_imo_number,
            $eez,
            $owner_informed_datetime,
            $phone_number,
            $first_notice,
            $after_72hr_boat_status,
            $date_of_investigation,
            $called_owner_to_inform_dc,
            $test_message_correct,
            $departure_date,
            $after_72hr_remark,
            $remarks,
            $username,
            $_SESSION['user_id'],
            $today_date
        ]);

        // centralized audit record (non-fatal)
        try {
            require_once __DIR__ . '/audit_helpers.php';
            $newId = $pdo->lastInsertId();
            $newRow = [
                'vessel_imo_number' => $vessel_imo_number,
                'eez' => $eez,
                'owner_informed_datetime' => $owner_informed_datetime,
                'phone_number' => $phone_number,
                'first_notice' => $first_notice,
                'after_72hr_boat_status' => $after_72hr_boat_status,
                'date_of_investigation' => $date_of_investigation,
                'called_owner_to_inform_dc' => $called_owner_to_inform_dc,
                'test_message_correct' => $test_message_correct,
                'departure_date' => $departure_date,
                'after_72hr_remark' => $after_72hr_remark,
                'remarks' => $remarks,
                'username' => $username,
                'created_by' => $_SESSION['user_id'],
                'created_at' => $today_date
            ];
            record_audit($pdo, 'border_crossings', $newId, 'create', $newRow);
        } catch (Exception $e) {
            error_log('add_border_crossing audit failed: ' . $e->getMessage());
        }

        echo "<script>alert('Successfully added!'); window.location.href = 'border_crossing.php';</script>";
        exit();
    } catch (Exception $e) {
        $msg = addslashes($e->getMessage());
        echo "<script>alert('Unsuccessful: $msg'); window.location.href = 'add_border_crossing.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Border Crossing - FMC Fisheries</title>
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
            <h1>Add New Border Crossing</h1>
            <form method="POST" action="add_border_crossing.php" class="form-grid" id="borderCrossingForm">
                <fieldset>
                    <legend>Vessel Details</legend>
                    <div class="form-group">
                        <label for="vessel_imo_number">Vessel IMO Number *</label>
                        <input type="text" name="vessel_imo_number" id="vessel_imo_number" required>
                    </div>
                    <div class="form-group">
                        <label for="eez">EEZ *</label>
                        <input type="text" name="eez" id="eez" required>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Owner Informed</legend>
                    <div class="form-group">
                        <label for="owner_informed_date">Owner Informed - Date</label>
                        <input type="date" name="owner_informed_date" id="owner_informed_date">
                    </div>
                    <div class="form-group">
                        <label for="owner_informed_phone">Owner Phone Number</label>
                        <input type="text" name="owner_informed_phone" id="owner_informed_phone">
                    </div>
                    <div class="form-group">
                        <label for="first_notice">First Notice</label>
                        <select name="first_notice" id="first_notice">
                            <option value="">Select or type</option>
                            <option value="Owner Informed">Owner Informed</option>
                            <option value="Inform to habour">Inform to habour</option>
                            <option value="Wrong number">Wrong number</option>
                            <option value="Did not respond">Did not respond</option>
                            <option value="Not working">Not working</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" name="first_notice_other" id="first_notice_other" style="display: none; margin-top: 5px;" placeholder="Type your own status">
                        <input type="hidden" name="first_notice_final" id="first_notice_final">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>After 72 Hours</legend>
                    <div class="form-group">
                        <label for="still_inside">Still Inside</label>
                        <select name="still_inside" id="still_inside">
                            <option value=""></option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sent_to_investigation">Sent to Investigation</label>
                        <select name="sent_to_investigation" id="sent_to_investigation">
                            <option value=""></option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_of_submission">Date of Submission</label>
                        <input type="date" name="date_of_submission" id="date_of_submission">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Departuere Cancel inform the owner</legend>
                    <div class="form-group">
                        <label for="called_owner_date">Called Owner - Date</label>
                        <input type="date" name="called_owner_date" id="called_owner_date">
                    </div>
                    <div class="form-group">
                        <label for="called_owner_phone">Called Owner - Phone Number</label>
                        <input type="text" name="called_owner_phone" id="called_owner_phone">
                    </div>
                    <div class="form-group">
                        <label for="called_owner_status">Departure Cancel inform the owner - Status</label>
                        <select name="called_owner_status" id="called_owner_status">
                            <option value="">Select or type</option>
                            <option value="Owner Informed">Owner Informed</option>
                            <option value="Inform to habour">Inform to habour</option>
                            <option value="Wrong number">Wrong number</option>
                            <option value="Did not respond">Did not respond</option>
                            <option value="Not working">Not working</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" name="called_owner_status_other" id="called_owner_status_other" style="display: none; margin-top: 5px;" placeholder="Type your own status">
                        <input type="hidden" name="called_owner_status_final" id="called_owner_status_final">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Text Message</legend>
                    <div class="form-group">
                        <label for="text_message_date">Text Message - Date</label>
                        <input type="date" name="text_message_date" id="text_message_date">
                    </div>
                    <div class="form-group">
                        <label for="text_message_content">Text Message</label>
                        <select name="text_message_content" id="text_message_content">
                            <option value=""></option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Departure/Remarks</legend>
                    <div class="form-group">
                        <label for="departure_cancel_date">Departure cancel Date</label>
                        <input type="date" name="departure_cancel_date" id="departure_cancel_date">
                    </div>
                    <div class="form-group">
                        <label for="after_72hr_remark">After 72 Hour Remark</label>
                        <input type="text" name="after_72hr_remark" id="after_72hr_remark">
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
        document.getElementById('borderCrossingForm').addEventListener('submit', function(event) {
            const ownerPhone = document.getElementById('owner_informed_phone').value;
            const calledOwnerPhone = document.getElementById('called_owner_phone').value;
            const phoneError = document.getElementById('phoneError');
            const firstNoticeSelect = document.getElementById('first_notice');
            const firstNoticeOther = document.getElementById('first_notice_other');
            const firstNoticeFinal = document.getElementById('first_notice_final');
            const calledOwnerStatusSelect = document.getElementById('called_owner_status');
            const calledOwnerStatusOther = document.getElementById('called_owner_status_other');
            const calledOwnerStatusFinal = document.getElementById('called_owner_status_final');

            if (ownerPhone && !/^\d{10}$/.test(ownerPhone)) {
                event.preventDefault();
                phoneError.textContent = 'Owner Phone must be exactly 10 digits.';
                phoneError.style.display = 'block';
                return;
            }
            if (calledOwnerPhone && !/^\d{10}$/.test(calledOwnerPhone)) {
                event.preventDefault();
                phoneError.textContent = 'Called Owner Phone must be exactly 10 digits.';
                phoneError.style.display = 'block';
                return;
            }
            phoneError.style.display = 'none';

            // Update hidden inputs with final values
            if (firstNoticeSelect.value === 'other' && firstNoticeOther.value) {
                firstNoticeFinal.value = firstNoticeOther.value;
            } else {
                firstNoticeFinal.value = firstNoticeSelect.value;
            }
            console.log('First Notice Final Value:', firstNoticeFinal.value); // Debug log

            if (calledOwnerStatusSelect.value === 'other' && calledOwnerStatusOther.value) {
                calledOwnerStatusFinal.value = calledOwnerStatusOther.value;
            } else {
                calledOwnerStatusFinal.value = calledOwnerStatusSelect.value;
            }
        });

        const firstNoticeSelect = document.getElementById('first_notice');
        const firstNoticeOther = document.getElementById('first_notice_other');
        const firstNoticeFinal = document.getElementById('first_notice_final');

        firstNoticeSelect.addEventListener('change', function() {
            if (firstNoticeSelect.value === 'other') {
                firstNoticeOther.style.display = 'block';
                firstNoticeOther.focus();
            } else {
                firstNoticeOther.style.display = 'none';
                firstNoticeOther.value = '';
            }
            firstNoticeFinal.value = firstNoticeSelect.value === 'other' ? firstNoticeOther.value : firstNoticeSelect.value;
        });

        firstNoticeOther.addEventListener('input', function() {
            if (firstNoticeSelect.value === 'other') {
                firstNoticeFinal.value = firstNoticeOther.value;
            }
        });

        const calledOwnerStatusSelect = document.getElementById('called_owner_status');
        const calledOwnerStatusOther = document.getElementById('called_owner_status_other');
        const calledOwnerStatusFinal = document.getElementById('called_owner_status_final');

        calledOwnerStatusSelect.addEventListener('change', function() {
            if (calledOwnerStatusSelect.value === 'other') {
                calledOwnerStatusOther.style.display = 'block';
                calledOwnerStatusOther.focus();
            } else {
                calledOwnerStatusOther.style.display = 'none';
                calledOwnerStatusOther.value = '';
            }
            calledOwnerStatusFinal.value = calledOwnerStatusSelect.value === 'other' ? calledOwnerStatusOther.value : calledOwnerStatusSelect.value;
        });

        calledOwnerStatusOther.addEventListener('input', function() {
            if (calledOwnerStatusSelect.value === 'other') {
                calledOwnerStatusFinal.value = calledOwnerStatusOther.value;
            }
        });
    </script>
</body>
</html>
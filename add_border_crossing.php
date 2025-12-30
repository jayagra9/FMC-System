<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$today_date = $sri_lanka_time->format('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
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

    // Format
    $owner_informed_datetime = $owner_informed_datetime ? $owner_informed_datetime . ' 00:00:00' : null;
    $date_of_investigation = $date_of_investigation ? $date_of_investigation . ' 00:00:00' : null;
    $called_owner_date = $called_owner_date ? $called_owner_date . ' 00:00:00' : null;
    $text_message_date = $text_message_date ? $text_message_date . ' 00:00:00' : null;
    $departure_date = $departure_date ? $departure_date . ' 00:00:00' : null;

    $after_72hr_boat_status = $still_inside . '|' . $sent_to_investigation;
    $called_owner_to_inform_dc = ($called_owner_date ?: '') . '|' . ($called_owner_phone ?: '') . '|' . ($called_owner_status ?: '');
    $test_message_correct = ($text_message_date ?: '') . '|' . ($text_message_content ?: '');

    if (empty($vessel_imo_number) || empty($eez)) {
        echo "<script>alert('Vessel IMO Number and EEZ are required');window.location.href='add_border_crossing.php';</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO border_crossings (
                vessel_imo_number, eez, owner_informed_datetime, phone_number,
                first_notice, after_72hr_boat_status, date_of_investigation,
                called_owner_to_inform_dc, test_message_correct,
                departure_date, after_72hr_remark, remarks,
                username, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $vessel_imo_number, $eez, $owner_informed_datetime, $phone_number,
            $first_notice, $after_72hr_boat_status, $date_of_investigation,
            $called_owner_to_inform_dc, $test_message_correct,
            $departure_date, $after_72hr_remark, $remarks,
            $_SESSION['username'], $_SESSION['user_id'], $today_date
        ]);

        echo "<script>alert('Successfully added!');window.location.href='border_crossing.php';</script>";
        exit();

    } catch (Exception $e) {
        $msg = addslashes($e->getMessage());
        echo "<script>alert('Unsuccessful: $msg');window.location.href='add_border_crossing.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Border Crossing – FMC Fisheries</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
body {
    font-family: Arial, sans-serif;
    background: #D1D5DB;
}
.container {
    padding: 30px;
    max-width: 1100px;
    margin: auto;
}
.card {
    background: #e8ecf3ff;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
}
h1 {
    text-align: center;
    color: #1E3A8A;
    font-size: 1.9rem;
    margin-bottom: 25px;
    font-weight: bold;
}
.form-grid {
    display: grid;
    gap: 20px;
}
fieldset {
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    padding: 20px;
    border-color: #aeb5c9ff;
}
legend {
    font-weight: bold;
    padding: 0 10px;
    color: #1E3A8A;
    font-size: 1.1rem;
}
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-weight: 500;
    margin-bottom: 6px;
}
input, select {
    padding: 10px;
    border: 1px solid #D1D5DB;
    border-radius: 4px;
    font-size: 0.95rem;
}
.btn-primary {
    background: #2DD4BF;
    color: #fff;
    padding: 14px 28px;
    font-size: 1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    margin-top: 15px;
}
.btn-primary:hover {
    background: #0D9488;
}
#first_notice_other, #called_owner_status_other {
    display:none;
    margin-top: 8px;
}
</style>
</head>
<body>
<?php require_once 'nav.php'; ?>

<div class="container">
    <div class="card">
        <h1>Add New Border Crossing</h1>

        <form method="POST" class="form-grid">

            <fieldset>
                <legend>Vessel Details</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label>Vessel IMO Number *</label>
                        <input type="text" name="vessel_imo_number" required>
                    </div>
                    <div class="form-group">
                        <label>EEZ *</label>
                        <input type="text" name="eez" required>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Owner Informed</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label>Owner Informed - Date</label>
                        <input type="date" name="owner_informed_date">
                    </div>
                    <div class="form-group">
                        <label>Owner Phone</label>
                        <input type="text" name="owner_informed_phone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Notice</label>
                        <select id="first_notice">
                            <option value="">Select</option>
                            <option>Owner Informed</option>
                            <option>Inform to habour</option>
                            <option>Wrong number</option>
                            <option>Did not respond</option>
                            <option>Not working</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="first_notice_other" placeholder="Enter custom notice">
                        <input type="hidden" name="first_notice_final" id="first_notice_final">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>After 72 Hours</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label>Still Inside</label>
                        <select name="still_inside">
                            <option value=""></option>
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Sent to Investigation</label>
                        <select name="sent_to_investigation">
                            <option value=""></option>
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Submission</label>
                        <input type="date" name="date_of_submission">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Called Owner</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label>Called Owner - Date</label>
                        <input type="date" name="called_owner_date">
                    </div>
                    <div class="form-group">
                        <label>Called Owner - Phone</label>
                        <input type="text" name="called_owner_phone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Called Owner Status</label>
                        <select id="called_owner_status">
                            <option value="">Select</option>
                            <option>Owner Informed</option>
                            <option>Inform to habour</option>
                            <option>Wrong number</option>
                            <option>Did not respond</option>
                            <option>Not working</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="text" id="called_owner_status_other" placeholder="Enter custom status">
                        <input type="hidden" name="called_owner_status_final" id="called_owner_status_final">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Text Message</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label>Text Message - Date</label>
                        <input type="date" name="text_message_date">
                    </div>
                    <div class="form-group">
                        <label>Text Message</label>
                        <select name="text_message_content">
                            <option value=""></option>
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Departure / Remarks</legend>
                <div class="form-row">
                    <div class="form-group">
                        <label>Departure Cancel Date</label>
                        <input type="date" name="departure_cancel_date">
                    </div>
                    <div class="form-group">
                        <label>After 72 Hour Remark</label>
                        <input type="text" name="after_72hr_remark">
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <input type="text" name="remarks">
                    </div>
                </div>
            </fieldset>

            <button type="submit" name="save" class="btn-primary"><i class="fas fa-save"></i> Save</button>

        </form>
    </div>
</div>

<script>
document.getElementById('first_notice').addEventListener('change', function() {
    const other = document.getElementById('first_notice_other');
    const hidden = document.getElementById('first_notice_final');
    if (this.value === 'other') {
        other.style.display = 'block';
        hidden.value = '';
    } else {
        other.style.display = 'none';
        hidden.value = this.value;
    }
});
document.getElementById('first_notice_other').addEventListener('input', function() {
    document.getElementById('first_notice_final').value = this.value;
});
document.getElementById('called_owner_status').addEventListener('change', function() {
    const other = document.getElementById('called_owner_status_other');
    const hidden = document.getElementById('called_owner_status_final');
    if (this.value === 'other') {
        other.style.display = 'block';
        hidden.value = '';
    } else {
        other.style.display = 'none';
        hidden.value = this.value;
    }
});
document.getElementById('called_owner_status_other').addEventListener('input', function() {
    document.getElementById('called_owner_status_final').value = this.value;
});
</script>

</body>
</html>

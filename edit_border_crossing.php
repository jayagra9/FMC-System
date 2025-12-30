<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "Invalid ID";
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM border_crossings WHERE id = ?");
$stmt->execute([$id]);
$crossing = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$crossing) {
    echo "Record not found";
    exit();
}

$sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
$today_date = $sri_lanka_time->format('Y-m-d H:i:s');

/* ---------------- SAVE UPDATE ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    $vessel_imo_number = $_POST['vessel_imo_number'];
    $eez = $_POST['eez'];

    $owner_informed_date = $_POST['owner_informed_date'] ?: null;
    $owner_phone = $_POST['owner_informed_phone'];

    $first_notice = $_POST['first_notice_final'];

    $still_inside = $_POST['still_inside'];
    $sent_to_investigation = $_POST['sent_to_investigation'];
    $date_of_submission = $_POST['date_of_submission'] ?: null;

    $called_owner_date = $_POST['called_owner_date'] ?: null;
    $called_owner_phone = $_POST['called_owner_phone'];
    $called_owner_status = $_POST['called_owner_status_final'];

    $text_message_date = $_POST['text_message_date'] ?: null;
    $text_message_content = $_POST['text_message_content'];

    $departure_cancel_date = $_POST['departure_cancel_date'] ?: null;
    $after_72hr_remark = $_POST['after_72hr_remark'];
    $remarks = $_POST['remarks'];

    // Format dates
    $owner_informed_date = $owner_informed_date ? $owner_informed_date . ' 00:00:00' : null;
    $date_of_submission = $date_of_submission ? $date_of_submission . ' 00:00:00' : null;
    $called_owner_date = $called_owner_date ? $called_owner_date . ' 00:00:00' : null;
    $text_message_date = $text_message_date ? $text_message_date . ' 00:00:00' : null;
    $departure_cancel_date = $departure_cancel_date ? $departure_cancel_date . ' 00:00:00' : null;

    $after_72hr_boat_status = $still_inside . '|' . $sent_to_investigation;
    $called_owner_to_inform_dc = ($called_owner_date ?: '') . '|' . ($called_owner_phone ?: '') . '|' . ($called_owner_status ?: '');
    $test_message_correct = ($text_message_date ?: '') . '|' . ($text_message_content ?: '');

    $stmt = $pdo->prepare("
        UPDATE border_crossings SET
            vessel_imo_number = ?, eez = ?, owner_informed_datetime = ?, phone_number = ?,
            first_notice = ?, after_72hr_boat_status = ?, date_of_investigation = ?,
            called_owner_to_inform_dc = ?, test_message_correct = ?, departure_date = ?,
            after_72hr_remark = ?, remarks = ?, updated_by = ?, updated_at = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $vessel_imo_number,
        $eez,
        $owner_informed_date,
        $owner_phone,
        $first_notice,
        $after_72hr_boat_status,
        $date_of_submission,
        $called_owner_to_inform_dc,
        $test_message_correct,
        $departure_cancel_date,
        $after_72hr_remark,
        $remarks,
        $_SESSION['user_id'],
        $today_date,
        $id
    ]);

    echo "<script>alert('Changes saved successfully');window.location.href='border_crossing.php';</script>";
    exit();
}

/* ----------- SPLIT STORED VALUES ----------- */
$after72 = explode('|', $crossing['after_72hr_boat_status'] ?? '|');
$calledOwner = explode('|', $crossing['called_owner_to_inform_dc'] ?? '||');
$textMessage = explode('|', $crossing['test_message_correct'] ?? '|');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Border Crossing – FMC Fisheries</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body{background:#D1D5DB;font-family:Arial}
.container{max-width:1100px;margin:auto;padding:30px}
.card{background:#e8ecf3ff;padding:30px;border-radius:8px;box-shadow:0 4px 18px rgba(0,0,0,.08)}
h1{text-align:center;color:#1E3A8A;margin-bottom:25px}
.form-grid{display:grid;gap:20px}
fieldset{border:1px solid #aeb5c9;border-radius:8px;padding:20px}
legend{font-weight:bold;color:#1E3A8A;padding:0 10px}
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}
.form-group{display:flex;flex-direction:column}
label{font-weight:500;margin-bottom:6px}
input,select{padding:10px;border:1px solid #D1D5DB;border-radius:4px}
.btn-primary{background:#2DD4BF;color:#fff;padding:14px;border:none;border-radius:5px;font-size:1rem;width:100%;margin-top:15px}
.btn-primary:hover{background:#0D9488}
#first_notice_other,#called_owner_status_other{display:none;margin-top:8px}
</style>
</head>

<body>
<?php require_once 'nav.php'; ?>

<div class="container">
<div class="card">
<h1>Edit Border Crossing</h1>

<form method="POST" class="form-grid">

<!-- Vessel -->
<fieldset>
<legend>Vessel Details</legend>
<div class="form-row">
<div class="form-group">
<label>Vessel IMO Number *</label>
<input name="vessel_imo_number" value="<?= htmlspecialchars($crossing['vessel_imo_number']) ?>" required>
</div>
<div class="form-group">
<label>EEZ *</label>
<input name="eez" value="<?= htmlspecialchars($crossing['eez']) ?>" required>
</div>
</div>
</fieldset>

<!-- Owner -->
<fieldset>
<legend>Owner Informed</legend>
<div class="form-row">
<div class="form-group">
<label>Date</label>
<input type="date" name="owner_informed_date" value="<?= substr($crossing['owner_informed_datetime'],0,10) ?>">
</div>
<div class="form-group">
<label>Phone</label>
<input name="owner_informed_phone" value="<?= htmlspecialchars($crossing['phone_number']) ?>">
</div>
</div>

<div class="form-group">
<label>First Notice</label>
<input name="first_notice_final" value="<?= htmlspecialchars($crossing['first_notice']) ?>">
</div>
</fieldset>

<!-- After 72 -->
<fieldset>
<legend>After 72 Hours</legend>
<div class="form-row">
<div class="form-group">
<label>Still Inside</label>
<select name="still_inside">
<option value=""></option>
<option <?= ($after72[0] ?? '')=='Yes'?'selected':'' ?>>Yes</option>
<option <?= ($after72[0] ?? '')=='No'?'selected':'' ?>>No</option>
</select>
</div>
<div class="form-group">
<label>Sent to Investigation</label>
<select name="sent_to_investigation">
<option value=""></option>
<option <?= ($after72[1] ?? '')=='Yes'?'selected':'' ?>>Yes</option>
<option <?= ($after72[1] ?? '')=='No'?'selected':'' ?>>No</option>
</select>
</div>
<div class="form-group">
<label>Date of Submission</label>
<input type="date" name="date_of_submission" value="<?= substr($crossing['date_of_investigation'],0,10) ?>">
</div>
</div>
</fieldset>

<!-- Called Owner -->
<fieldset>
<legend>Called Owner</legend>
<div class="form-row">
<div class="form-group">
<label>Date</label>
<input type="date" name="called_owner_date" value="<?= substr($calledOwner[0] ?? '',0,10) ?>">
</div>
<div class="form-group">
<label>Phone</label>
<input name="called_owner_phone" value="<?= htmlspecialchars($calledOwner[1] ?? '') ?>">
</div>
</div>

<div class="form-group">
<label>Status</label>
<input name="called_owner_status_final" value="<?= htmlspecialchars($calledOwner[2] ?? '') ?>">
</div>
</fieldset>

<!-- Text -->
<fieldset>
<legend>Text Message</legend>
<div class="form-row">
<div class="form-group">
<label>Date</label>
<input type="date" name="text_message_date" value="<?= substr($textMessage[0] ?? '',0,10) ?>">
</div>
<div class="form-group">
<label>Message Sent</label>
<select name="text_message_content">
<option value=""></option>
<option <?= ($textMessage[1] ?? '')=='Yes'?'selected':'' ?>>Yes</option>
<option <?= ($textMessage[1] ?? '')=='No'?'selected':'' ?>>No</option>
</select>
</div>
</div>
</fieldset>

<!-- Remarks -->
<fieldset>
<legend>Departure / Remarks</legend>
<div class="form-row">
<div class="form-group">
<label>Departure Cancel Date</label>
<input type="date" name="departure_cancel_date" value="<?= substr($crossing['departure_date'],0,10) ?>">
</div>
<div class="form-group">
<label>After 72 Hour Remark</label>
<input name="after_72hr_remark" value="<?= htmlspecialchars($crossing['after_72hr_remark']) ?>">
</div>
<div class="form-group">
<label>Remarks</label>
<input name="remarks" value="<?= htmlspecialchars($crossing['remarks']) ?>">
</div>
</div>
</fieldset>

<button type="submit" name="save" class="btn-primary">
<i class="fas fa-save"></i> Save Changes
</button>

</form>
</div>
</div>
</body>
</html>

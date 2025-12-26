<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $record_number = 'SRV-' . date('Y') . '-' . rand(1000,9999);

    $stmt = $pdo->prepare("
        INSERT INTO services (
            record_number, iom_zms, imul_no, bt_sn,
            installed_date, home_port, contact_number,
            current_status, feedback_to_call,
            service_done_date, comments,
            installation_checklist, username, created_by, created_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
    ");

    $stmt->execute([
        $record_number,
        $_POST['iom_zms'],
        $_POST['imul_no'],
        $_POST['bt_sn'],
        $_POST['installed_date'],
        $_POST['home_port'],
        $_POST['contact_number'],
        $_POST['current_status'],
        $_POST['feedback_final'],
        $_POST['service_done_date'],
        $_POST['comments_final'],
        implode(',', $_POST['checklist'] ?? []),
        $_SESSION['username'],
        $_SESSION['user_id']
    ]);

    echo "<script>alert('Service Added');location='services.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Service</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">
<style>
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }
.form-group { display:flex; flex-direction:column; }
input, select { padding:10px; border:1px solid #D1D5DB; border-radius:4px; }
fieldset { border:1px solid #D1D5DB; padding:15px; }
.btn-primary { background:#2DD4BF; color:#fff; padding:12px; border:none; }
</style>
</head>
<body>
<?php require_once 'nav.php'; ?>

<div class="container">
<div class="card">
<h1>Add Service</h1>

<form method="POST" class="form-grid">

<fieldset>
<select name="iom_zms" required>
<option value="">IOM / ZMS</option>
<option>IOM</option>
<option>ZMS</option>
</select>

<input name="imul_no" placeholder="IMUL No" required>
<input name="bt_sn" placeholder="BT SN">
<input type="date" name="installed_date">
<input name="home_port" placeholder="Home Port">
<input name="contact_number" placeholder="Contact Number">

<select name="current_status">
<option>Working</option>
<option>Service pending</option>
<option>Repair required</option>
<option>Awaiting payment</option>
</select>
</fieldset>

<fieldset>
<select id="feedback_select">
<option value="">Feedback to Call</option>
<option>I will send bank details for whatsapp</option>
<option>This number not be connected</option>
<option>Switch off & currently unavailable</option>
<option>The boat is at sea</option>
<option>No answer</option>
<option>Service done</option>
<option>Repairs</option>
<option>Money problem</option>
<option>Informed</option>
<option>Other</option>
</select>
<input id="feedback_other" style="display:none;">
<input type="hidden" name="feedback_final" id="feedback_final">
</fieldset>

<fieldset>
<input type="date" name="service_done_date">

<select id="comments_select">
<option value="">Comments</option>
<option>Service done</option>
<option>Allow departure for this time</option>
<option>FW Update completed</option>
<option>FW completed</option>
<option>Agree to get done the service before next departure</option>
<option>Other</option>
</select>
<input id="comments_other" style="display:none;">
<input type="hidden" name="comments_final" id="comments_final">
</fieldset>

<fieldset>
<label><input type="checkbox" name="checklist[]" value="Power OK"> Power OK</label>
<label><input type="checkbox" name="checklist[]" value="GPS OK"> GPS OK</label>
<label><input type="checkbox" name="checklist[]" value="FW Updated"> FW Updated</label>
</fieldset>

<button class="btn-primary">Save</button>
</form>
</div>
</div>

<script>
function bind(selectId, otherId, hiddenId){
const s=document.getElementById(selectId),
o=document.getElementById(otherId),
h=document.getElementById(hiddenId);
s.onchange=()=>{ if(s.value==='Other'){o.style.display='block';h.value=o.value;}else{o.style.display='none';h.value=s.value;}};
o.oninput=()=>h.value=o.value;
}
bind('feedback_select','feedback_other','feedback_final');
bind('comments_select','comments_other','comments_final');
</script>
</body>
</html>

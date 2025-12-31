<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_number = 'SRV-' . date('Y') . '-' . rand(1000, 9999);
    $checklist = implode(', ', $_POST['checklist'] ?? []);

    $stmt = $pdo->prepare("
        INSERT INTO services (
            record_number, iom_zms, imul_no, bt_sn,
            installed_date, home_port, contact_number, current_status,
            feedback_to_call, service_done_date, comments, installation_checklist,
            username, created_by, created_at
        ) VALUES (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()
        )
    ");

    $stmt->execute([
        $record_number,
        $_POST['iom_zms'],
        $_POST['imul_no'],
        $_POST['bt_sn'],
        $_POST['installed_date'] ?: null,
        $_POST['home_port'],
        $_POST['contact_number'],
        $_POST['current_status'],
        $_POST['feedback_final'],
        $_POST['service_done_date'] ?: null,
        $_POST['comments_final'],
        $checklist,
        $_SESSION['username'],
        $_SESSION['user_id']
    ]);

    echo "<script>alert('Service record added successfully'); window.location.href = 'service.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Service – FMC Fisheries</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">

<style>
.container { max-width: 1200px; margin: auto; }

h1 { text-align:center; margin-bottom: 30px; }

fieldset {
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

legend {
    font-weight: 600;
    color: #1E3A8A;
    padding: 0 10px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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
}

.checklist-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.checklist-table td {
    padding: 12px;
    border: 1px solid #E5E7EB;
}

.checklist-table label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.btn-primary {
    background-color: #2DD4BF;
    color: #fff;
    padding: 14px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    width: 100%;
}

.btn-primary:hover {
    background-color: #0D9488;
}
</style>
</head>

<body>
<?php require_once 'nav.php'; ?>

<div class="container">
<div class="card">

<h1>Add New Service Record</h1>

<form method="POST">

<!-- Device Information -->
<fieldset>
<legend>Device Information</legend>
<div class="form-grid">
    <div class="form-group">
        <label>IOM / ZMS</label>
        <select name="iom_zms" required>
            <option value="">Select</option>
            <option value="IOM">IOM</option>
            <option value="ZMS">ZMS</option>
        </select>
    </div>

    <div class="form-group">
        <label>IMUL Number</label>
        <input name="imul_no" required>
    </div>

    <div class="form-group">
        <label>BT Serial Number</label>
        <input name="bt_sn">
    </div>

    <div class="form-group">
        <label>Installed Date</label>
        <input type="date" name="installed_date">
    </div>

    <div class="form-group">
        <label>Home Port</label>
        <input name="home_port">
    </div>

    <div class="form-group">
        <label>Contact Number</label>
        <input name="contact_number">
    </div>

    <div class="form-group">
        <label>Current Status</label>
        <select name="current_status">
            <option>Working</option>
            <option>Service pending</option>
            <option>Repair required</option>
            <option>Awaiting payment</option>
        </select>
    </div>
</div>
</fieldset>

<!-- Feedback -->
<fieldset>
<legend>Feedback to Call</legend>
<div class="form-grid">
    <div class="form-group">
        <label>Feedback</label>
        <select id="feedback_select">
            <option value="">Select</option>
            <option>I will send bank details for whatsapp</option>
            <option>This number not be connected</option>
            <option>Switch off & currently unavailable</option>
            <option>The boat is at sea</option>
            <option>No answer</option>
            <option>Service done</option>
            <option>Repairs</option>
            <option>Money problem</option>
            <option>Informed</option>
            <option value="Other">Other</option>
        </select>
        <input id="feedback_other" placeholder="Type feedback" style="display:none;margin-top:8px;">
        <input type="hidden" name="feedback_final" id="feedback_final">
    </div>
</div>
</fieldset>

<!-- Service Completion -->
<fieldset>
<legend>Service Completion</legend>
<div class="form-grid">
    <div class="form-group">
        <label>Service Done Date</label>
        <input type="date" name="service_done_date">
    </div>

    <div class="form-group">
        <label>Comments</label>
        <select id="comments_select">
            <option value="">Select</option>
            <option>Service done</option>
            <option>Allow departure for this time</option>
            <option>FW Update completed</option>
            <option>FW completed</option>
            <option>Agree to get done the service before next departure</option>
            <option value="Other">Other</option>
        </select>
        <input id="comments_other" placeholder="Type comment" style="display:none;margin-top:8px;">
        <input type="hidden" name="comments_final" id="comments_final">
    </div>
</div>
</fieldset>

<!-- Checklist -->
<fieldset>
<legend>Installation Checklist</legend>
<table class="checklist-table">
<tr>
<td><label><input type="checkbox" name="checklist[]" value="Power OK"> Power OK</label></td>
<td><label><input type="checkbox" name="checklist[]" value="GPS OK"> GPS OK</label></td>
</tr>
<tr>
<td><label><input type="checkbox" name="checklist[]" value="Firmware Updated"> Firmware Updated</label></td>
<td><label><input type="checkbox" name="checklist[]" value="Antenna Fixed"> Antenna Fixed</label></td>
</tr>
<tr>
<td><label><input type="checkbox" name="checklist[]" value="Battery OK"> Battery OK</label></td>
<td></td>
</tr>
</table>
</fieldset>

<button type="submit" name="save" class="btn-primary">Save</button>

</form>
</div>
</div>

<script>
function bind(selectId, otherId, hiddenId){
    const s=document.getElementById(selectId);
    const o=document.getElementById(otherId);
    const h=document.getElementById(hiddenId);

    s.onchange=()=>{
        if(s.value==='Other'){
            o.style.display='block';
            h.value=o.value;
        } else {
            o.style.display='none';
            h.value=s.value;
        }
    };
    o.oninput=()=>h.value=o.value;
}

bind('feedback_select','feedback_other','feedback_final');
bind('comments_select','comments_other','comments_final');
</script>

</body>
</html>

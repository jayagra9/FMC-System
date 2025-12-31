<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$service = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$service) {
    echo "<script>alert('Record not found');location='service.php';</script>";
    exit();
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    $checklist = implode(', ', $_POST['checklist'] ?? []);

    $stmt = $pdo->prepare("
        UPDATE services SET
            iom_zms = ?,
            imul_no = ?,
            bt_sn = ?,
            installed_date = ?,
            home_port = ?,
            contact_number = ?,
            current_status = ?,
            feedback_to_call = ?,
            service_done_date = ?,
            comments = ?,
            installation_checklist = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
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
        $_SESSION['user_id'],
        $id
    ]);

    echo "<script>alert('Service record updated successfully');location='service.php';</script>";
    exit();
}

$checked = array_map('trim', explode(',', $service['installation_checklist'] ?? ''));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Service – FMC Fisheries</title>

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

<h1>Edit Service Record</h1>

<form method="POST">

<!-- Device Information -->
<fieldset>
<legend>Device Information</legend>
<div class="form-grid">
    <div class="form-group">
        <label>IOM / ZMS</label>
        <select name="iom_zms">
            <option <?= $service['iom_zms']=='IOM'?'selected':'' ?>>IOM</option>
            <option <?= $service['iom_zms']=='ZMS'?'selected':'' ?>>ZMS</option>
        </select>
    </div>

    <div class="form-group">
        <label>IMUL Number</label>
        <input name="imul_no" value="<?= htmlspecialchars($service['imul_no']) ?>">
    </div>

    <div class="form-group">
        <label>BT Serial Number</label>
        <input name="bt_sn" value="<?= htmlspecialchars($service['bt_sn']) ?>">
    </div>

    <div class="form-group">
        <label>Installed Date</label>
        <input type="date" name="installed_date" value="<?= $service['installed_date'] ?>">
    </div>

    <div class="form-group">
        <label>Home Port</label>
        <input name="home_port" value="<?= htmlspecialchars($service['home_port']) ?>">
    </div>

    <div class="form-group">
        <label>Contact Number</label>
        <input name="contact_number" value="<?= htmlspecialchars($service['contact_number']) ?>">
    </div>

    <div class="form-group">
        <label>Current Status</label>
        <select name="current_status">
            <?php foreach(['Working','Service pending','Repair required','Awaiting payment'] as $s): ?>
                <option <?= $service['current_status']==$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
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
            <option <?= $service['feedback_to_call']=='Service done'?'selected':'' ?>>Service done</option>
            <option <?= $service['feedback_to_call']=='Money problem'?'selected':'' ?>>Money problem</option>
            <option value="Other">Other</option>
        </select>
        <input id="feedback_other" style="display:none;margin-top:8px;">
        <input type="hidden" name="feedback_final" id="feedback_final" value="<?= htmlspecialchars($service['feedback_to_call']) ?>">
    </div>
</div>
</fieldset>

<!-- Service Completion -->
<fieldset>
<legend>Service Completion</legend>
<div class="form-grid">
    <div class="form-group">
        <label>Service Done Date</label>
        <input type="date" name="service_done_date" value="<?= $service['service_done_date'] ?>">
    </div>

    <div class="form-group">
        <label>Comments</label>
        <select id="comments_select">
            <option value="">Select</option>
            <option <?= $service['comments']=='Service done'?'selected':'' ?>>Service done</option>
            <option <?= $service['comments']=='FW completed'?'selected':'' ?>>FW completed</option>
            <option value="Other">Other</option>
        </select>
        <input id="comments_other" style="display:none;margin-top:8px;">
        <input type="hidden" name="comments_final" id="comments_final" value="<?= htmlspecialchars($service['comments']) ?>">
    </div>
</div>
</fieldset>

<!-- Checklist -->
<fieldset>
<legend>Installation Checklist</legend>
<table class="checklist-table">
<tr>
<td><label><input type="checkbox" name="checklist[]" value="Power OK" <?= in_array('Power OK',$checked)?'checked':'' ?>> Power OK</label></td>
<td><label><input type="checkbox" name="checklist[]" value="GPS OK" <?= in_array('GPS OK',$checked)?'checked':'' ?>> GPS OK</label></td>
</tr>
<tr>
<td><label><input type="checkbox" name="checklist[]" value="Firmware Updated" <?= in_array('Firmware Updated',$checked)?'checked':'' ?>> Firmware Updated</label></td>
<td><label><input type="checkbox" name="checklist[]" value="Antenna Fixed" <?= in_array('Antenna Fixed',$checked)?'checked':'' ?>> Antenna Fixed</label></td>
</tr>
<tr>
<td><label><input type="checkbox" name="checklist[]" value="Battery OK" <?= in_array('Battery OK',$checked)?'checked':'' ?>> Battery OK</label></td>
<td></td>
</tr>
</table>
</fieldset>

<button type="submit" name="save" class="btn-primary">Save Changes</button>

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

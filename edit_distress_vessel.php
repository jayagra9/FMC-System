<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vessel = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM distress_vessels WHERE id = ?");
    $stmt->execute([$id]);
    $vessel = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    $data = [
        'date' => $_POST['date'] ? $_POST['date'] . ' 00:00:00' : null,
        'vessel_name' => trim($_POST['vessel_name']),
        'owner_name' => trim($_POST['owner_name']),
        'contact_number' => trim($_POST['contact_number']),
        'address' => trim($_POST['address']),
        'status' => trim($_POST['status']),
        'speed' => trim($_POST['speed']),
        'position' => trim($_POST['position']),
        'date_time_detection' => $_POST['date_time_detection']
            ? $_POST['date_time_detection'] . ':00'
            : null,
        'distance_last_position' => trim($_POST['distance_last_position']),
        'notes' => trim($_POST['notes']),
        'remark' => trim($_POST['remark']),
        'departure_form' => $vessel['departure_form'] ?? null,
        'voyage' => $vessel['voyage'] ?? null
    ];

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    foreach (['departure_form', 'voyage'] as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg'])) {
                echo "<script>alert('Only JPG images allowed');history.back();</script>";
                exit();
            }
            $path = $upload_dir . uniqid($field . '_') . '.' . $ext;
            move_uploaded_file($_FILES[$field]['tmp_name'], $path);
            $data[$field] = $path;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE distress_vessels SET
        date=?, vessel_name=?, owner_name=?, contact_number=?, address=?,
        status=?, speed=?, position=?, date_time_detection=?,
        distance_last_position=?, notes=?, remark=?,
        departure_form=?, voyage=?,
        updated_by=?, updated_at=NOW()
        WHERE id=?
    ");

    $stmt->execute([
        $data['date'], $data['vessel_name'], $data['owner_name'],
        $data['contact_number'], $data['address'], $data['status'],
        $data['speed'], $data['position'], $data['date_time_detection'],
        $data['distance_last_position'], $data['notes'], $data['remark'],
        $data['departure_form'], $data['voyage'],
        $_SESSION['user_id'], $id
    ]);

    echo "<script>alert('Updated successfully');window.location='distress_vessel.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Distress Vessel</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">
<style>
.container{max-width:1100px;margin:auto;padding:30px}
.card{background:#fff;padding:30px;border-radius:8px}
fieldset{border:1px solid #D1D5DB;padding:20px;margin-bottom:20px;border-radius:6px}
legend{font-weight:bold;color:#1E3A8A}
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}
.form-group{display:flex;flex-direction:column}
input,select{padding:10px;border:1px solid #D1D5DB;border-radius:4px}
.preview-img{max-width:120px;margin-top:8px;border-radius:4px}
.remove-btn{margin-top:8px;background:#dc2626;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer}
.remove-btn:hover{background:#b91c1c}
.btn-primary{background:#2DD4BF;color:#fff;padding:14px;border:none;border-radius:5px;width:100%;font-size:1rem}
.btn-primary:hover{background:#0D9488}
</style>
</head>

<body>
<?php require_once 'nav.php'; ?>

<div class="container">
<div class="card">

<h1>Edit Distress Vessel</h1>

<form method="POST" enctype="multipart/form-data">

<fieldset>
<legend>Vessel Details</legend>
<div class="form-row">
<div class="form-group">
<label>Date</label>
<input type="date" name="date" value="<?= !empty($vessel['date']) ? date('Y-m-d', strtotime($vessel['date'])) : '' ?>">
</div>
<div class="form-group">
<label>Vessel Name *</label>
<input name="vessel_name" value="<?= htmlspecialchars($vessel['vessel_name'] ?? '') ?>" required>
</div>
<div class="form-group">
<label>Owner</label>
<input name="owner_name" value="<?= htmlspecialchars($vessel['owner_name'] ?? '') ?>">
</div>
<div class="form-group">
<label>Contact</label>
<input name="contact_number" value="<?= htmlspecialchars($vessel['contact_number'] ?? '') ?>">
</div>
</div>
</fieldset>

<fieldset>
<legend>Position & Status</legend>
<div class="form-row">
<div class="form-group"><label>Status</label><input name="status" value="<?= htmlspecialchars($vessel['status'] ?? '') ?>"></div>
<div class="form-group"><label>Speed</label><input name="speed" value="<?= htmlspecialchars($vessel['speed'] ?? '') ?>"></div>
<div class="form-group"><label>Position</label><input name="position" value="<?= htmlspecialchars($vessel['position'] ?? '') ?>"></div>
<div class="form-group">
<label>Detected At</label>
<input type="datetime-local" name="date_time_detection"
value="<?= !empty($vessel['date_time_detection']) ? date('Y-m-d\TH:i', strtotime($vessel['date_time_detection'])) : '' ?>">
</div>
</div>
</fieldset>

<fieldset>
<legend>Images</legend>

<div class="form-group" id="departureBlock">
<label>Departure Form</label>
<input type="file" name="departure_form" accept="image/jpeg">
<?php if (!empty($vessel['departure_form'])): ?>
<img src="<?= htmlspecialchars($vessel['departure_form']) ?>" class="preview-img">
<button type="button" class="remove-btn"
onclick="removeImage(<?= $id ?>,'departure_form','departureBlock')">Remove</button>
<?php endif; ?>
</div>

<div class="form-group" id="voyageBlock">
<label>Voyage</label>
<input type="file" name="voyage" accept="image/jpeg">
<?php if (!empty($vessel['voyage'])): ?>
<img src="<?= htmlspecialchars($vessel['voyage']) ?>" class="preview-img">
<button type="button" class="remove-btn"
onclick="removeImage(<?= $id ?>,'voyage','voyageBlock')">Remove</button>
<?php endif; ?>
</div>

</fieldset>

<button class="btn-primary" name="save">Save Changes</button>

</form>
</div>
</div>

<script>
function removeImage(id, field, block) {
    if(!confirm('Remove this image?')) return;
    fetch('ajax_remove_distress_image.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id=${id}&field=${field}`
    })
    .then(r=>r.json())
    .then(d=>{
        if(d.success){
            document.getElementById(block).innerHTML='<p style="color:green">Image removed</p>';
        } else {
            alert(d.message || 'Failed');
        }
    });
}
</script>

</body>
</html>

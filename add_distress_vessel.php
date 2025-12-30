<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $data = [
        'date' => $_POST['date'] ?: null,
        'vessel_name' => trim($_POST['vessel_name']),
        'owner_name' => trim($_POST['owner_name']),
        'contact_number' => trim($_POST['contact_number']),
        'address' => trim($_POST['address']),
        'status' => trim($_POST['status']),
        'speed' => trim($_POST['speed']),
        'position' => trim($_POST['position']),
        'date_time_detection' => $_POST['date_time_detection'] ?: null,
        'distance_last_position' => trim($_POST['distance_last_position']),
        'notes' => trim($_POST['notes']),
        'remark' => trim($_POST['remark']),
        'departure_form' => null,
        'voyage' => null,
        'username' => $_SESSION['username']
    ];

    if ($data['date']) {
        $data['date'] .= ' 00:00:00';
    }
    if ($data['date_time_detection']) {
        $data['date_time_detection'] .= ':00';
    }

    $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
    $today_date = $sri_lanka_time->format('Y-m-d H:i:s');

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    foreach (['departure_form', 'voyage'] as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg'])) {
                $dest = $upload_dir . uniqid($field . '_') . '.' . $ext;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
                    $data[$field] = $dest;
                }
            }
        }
    }

    if (empty($data['vessel_name'])) {
        echo "<script>alert('Vessel Name is required');location='add_distress_vessel.php';</script>";
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO distress_vessels (
            date, vessel_name, owner_name, contact_number, address,
            status, speed, position, date_time_detection,
            distance_last_position, notes, remark,
            departure_form, voyage,
            username, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['date'],
        $data['vessel_name'],
        $data['owner_name'],
        $data['contact_number'],
        $data['address'],
        $data['status'],
        $data['speed'],
        $data['position'],
        $data['date_time_detection'],
        $data['distance_last_position'],
        $data['notes'],
        $data['remark'],
        $data['departure_form'],
        $data['voyage'],
        $data['username'],
        $_SESSION['user_id'],
        $today_date
    ]);

    echo "<script>alert('Successfully added!');window.location='distress_vessel.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Distress Vessel – FMC Fisheries</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body{background:#D1D5DB;font-family:Arial}
.container{max-width:1100px;margin:auto;padding:30px}
.card{background:#fff;padding:30px;border-radius:8px;box-shadow:0 4px 18px rgba(0,0,0,.08)}
h1{text-align:center;color:#1E3A8A;margin-bottom:25px;font-size:1.9rem;font-weight:bold}
.form-grid{display:grid;gap:20px}
fieldset{border:1px solid #aeb5c9;border-radius:8px;padding:20px}
legend{font-weight:bold;color:#1E3A8A;padding:0 10px}
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}
.form-group{display:flex;flex-direction:column}
label{font-weight:500;margin-bottom:6px}
input,select{padding:10px;border:1px solid #D1D5DB;border-radius:4px}
.btn-primary{background:#2DD4BF;color:#fff;padding:14px;border:none;border-radius:5px;font-size:1rem;width:100%}
.btn-primary:hover{background:#0D9488}
.preview-img{max-width:120px;margin-top:8px;border-radius:4px}
.remove-preview{color:#C00;margin-top:8px;cursor:pointer;font-size:0.9rem;text-decoration:underline}
.error-message{color:red;margin-top:10px}
</style>
</head>

<body>
<?php require_once 'nav.php'; ?>

<div class="container">
<div class="card">
<h1>Add Distress Vessel</h1>

<form method="POST" enctype="multipart/form-data" class="form-grid" id="distressVesselForm">

<fieldset>
<legend>Vessel Details</legend>
<div class="form-row">
<div class="form-group">
<label>Date</label>
<input type="date" name="date">
</div>
<div class="form-group">
<label>Name *</label>
<input type="text" name="vessel_name" required>
</div>
<div class="form-group">
<label>Owner Name</label>
<input type="text" name="owner_name">
</div>
<div class="form-group">
<label>Contact Number</label>
<input type="text" name="contact_number" id="contact_number">
</div>
<div class="form-group">
<label>Address</label>
<input type="text" name="address">
</div>
</div>
</fieldset>

<fieldset>
<legend>Vessel Status</legend>
<div class="form-row">
<div class="form-group"><label>Status</label><input name="status"></div>
<div class="form-group"><label>Speed</label><input name="speed"></div>
<div class="form-group"><label>Position</label><input name="position"></div>
<div class="form-group">
<label>Date & Time Detection</label>
<input type="datetime-local" name="date_time_detection">
</div>
<div class="form-group">
<label>Distance from Last Position</label>
<input name="distance_last_position">
</div>
</div>
</fieldset>

<fieldset>
<legend>Comments</legend>
<div class="form-row">
<div class="form-group"><label>Notes</label><input name="notes"></div>
<div class="form-group"><label>Remark</label><input name="remark"></div>
</div>
</fieldset>

<fieldset>
<legend>Attachments</legend>
<div class="form-row">

<div class="form-group" id="departurePreview">
<label>Departure Form (JPEG)</label>
<input type="file" name="departure_form" accept="image/jpeg" onchange="previewImage(this,'departurePreview')">
</div>

<div class="form-group" id="voyagePreview">
<label>Voyage (JPEG)</label>
<input type="file" name="voyage" accept="image/jpeg" onchange="previewImage(this,'voyagePreview')">
</div>

</div>
</fieldset>

<button class="btn-primary" name="save"><i class="fas fa-save"></i> Save</button>

</form>

<div class="error-message" id="phoneError"></div>
</div>
</div>

<script>
function previewImage(input, containerId) {
    const container = document.getElementById(containerId);
    const oldImg = container.querySelector('.preview-img');
    const oldRemove = container.querySelector('.remove-preview');
    if (oldImg) oldImg.remove();
    if (oldRemove) oldRemove.remove();

    const file = input.files[0];
    if (!file) return;

    const img = document.createElement('img');
    img.className = 'preview-img';
    img.src = URL.createObjectURL(file);

    const removeLink = document.createElement('span');
    removeLink.className = 'remove-preview';
    removeLink.textContent = 'Remove';
    removeLink.onclick = function() {
        input.value = '';
        img.remove();
        removeLink.remove();
    };

    container.appendChild(img);
    container.appendChild(removeLink);
}

document.getElementById('distressVesselForm').addEventListener('submit', function(e) {
    const phone = document.getElementById('contact_number').value;
    const err = document.getElementById('phoneError');
    if (phone && !/^\d{10}$/.test(phone)) {
        e.preventDefault();
        err.textContent = 'Contact Number must be 10 digits';
        return;
    }
    err.textContent = '';
});
</script>

</body>
</html>

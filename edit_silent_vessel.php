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
        'vessel_name' => $_POST['vessel_name'],
        'owner_name' => $_POST['owner_name'],
        'owner_contact_number' => $_POST['owner_contact_number'],
        'relevant_harbour' => $_POST['relevant_harbour'],
        'owner_information_date' => $_POST['owner_information_date'],
        'owner_informed' => $_POST['owner_informed'],
        'sms_to_owner' => $_POST['sms_to_owner'],
        'date_to_investigate' => $_POST['date_to_investigate'],
        'comment' => $_POST['comment'],
        'remarks' => $_POST['remarks']
    ];

    $data['owner_information_date'] = $data['owner_information_date'] ? $data['owner_information_date'] . ' 00:00:00' : null;
    $data['date_to_investigate'] = $data['date_to_investigate'] ? $data['date_to_investigate'] . ' 00:00:00' : null;

    $sri_lanka_time = new DateTime('now', new DateTimeZone('Asia/Colombo'));
    $today_date = $sri_lanka_time->format('Y-m-d H:i:s');

    if (empty($data['vessel_name'])) {
        echo "<script>alert('Vessel Name is required');window.location.href='edit_silent_vessel.php?id=" . htmlspecialchars($id) . "';</script>";
        exit();
    }

    try {
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
            $id
        ]);

        $newRow = $data;
        $newRow['updated_by'] = $_SESSION['user_id'];
        $newRow['updated_at'] = $today_date;

        $diff = [];
        foreach ($newRow as $col => $val) {
            $oldVal = $oldRow[$col] ?? null;
            if ((string)$oldVal !== (string)$val) {
                $diff[$col] = ['old' => $oldVal, 'new' => $val];
            }
        }

        try {
            require_once __DIR__ . '/audit_helpers.php';
            record_audit($pdo, 'silent_vessels', $id, 'update', $diff);
        } catch (Exception $ae) {
            error_log('audit record failed (silent update): ' . $ae->getMessage());
        }

        echo "<script>alert('Changes saved successfully!');window.location.href='silent_vessel.php';</script>";
        exit();
    } catch (Exception $e) {
        $msg = addslashes($e->getMessage());
        echo "<script>alert('Error: $msg');window.location.href='edit_silent_vessel.php?id=" . htmlspecialchars($id) . "';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Silent Vessel – FMC Fisheries</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #D1D5DB; font-family: Arial; }
        .container { max-width: 1100px; margin: auto; padding: 30px; }
        .card { background: #e8ecf3ff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
        h1 { text-align: center; color: #1E3A8A; margin-bottom: 25px; font-size: 1.9rem; font-weight: bold; }
        .form-grid { display: grid; gap: 20px; }
        fieldset { border: 1px solid #aeb5c9; border-radius: 8px; padding: 20px; }
        legend { font-weight: bold; color: #1E3A8A; padding: 0 10px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-weight: 500; margin-bottom: 6px; }
        input, select { padding: 10px; border: 1px solid #D1D5DB; border-radius: 4px; }
        .btn-primary { background: #2DD4BF; color: #fff; padding: 14px; font-size: 1rem; border: none; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 15px; }
        .btn-primary:hover { background: #0D9488; }
        .error-message { color: red; margin-top: 10px; }
    </style>
</head>
<body>
<?php require_once 'nav.php'; ?>
<div class="container">
    <div class="card">
        <?php if ($vessel): ?>
            <h1>Edit Silent Vessel</h1>
            <form method="POST" class="form-grid" id="silentVesselForm">
                <fieldset>
                    <legend>Vessel Details</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Vessel Name *</label>
                            <input name="vessel_name" value="<?= htmlspecialchars($vessel['vessel_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Owner Name</label>
                            <input name="owner_name" value="<?= htmlspecialchars($vessel['owner_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Owner Contact Number</label>
                            <input name="owner_contact_number" id="owner_contact_number" value="<?= htmlspecialchars($vessel['owner_contact_number']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Relevant Harbour</label>
                            <input name="relevant_harbour" value="<?= htmlspecialchars($vessel['relevant_harbour']) ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Owner Information</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Owner Information Date</label>
                            <input type="date" name="owner_information_date" value="<?= !empty($vessel['owner_information_date']) ? (new DateTime($vessel['owner_information_date']))->format('Y-m-d') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Owner Informed</label>
                            <select name="owner_informed">
                                <option value=""></option>
                                <option value="Yes" <?= $vessel['owner_informed'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $vessel['owner_informed'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>SMS to Owner</label>
                            <select name="sms_to_owner">
                                <option value=""></option>
                                <option value="Yes" <?= $vessel['sms_to_owner'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $vessel['sms_to_owner'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Investigation</legend>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date to Investigate</label>
                            <input type="date" name="date_to_investigate" value="<?= !empty($vessel['date_to_investigate']) ? (new DateTime($vessel['date_to_investigate']))->format('Y-m-d') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Comment</label>
                            <input name="comment" value="<?= htmlspecialchars($vessel['comment']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Remarks</label>
                            <input name="remarks" value="<?= htmlspecialchars($vessel['remarks']) ?>">
                        </div>
                    </div>
                </fieldset>

                <button type="submit" name="save" class="btn-primary"><i class="fas fa-save"></i> Save</button>
            </form>
            <div class="error-message" id="phoneError"></div>
        <?php else: ?>
            <p>Record not found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('silentVesselForm')?.addEventListener('submit', function(e) {
    const phone = document.getElementById('owner_contact_number').value;
    const err = document.getElementById('phoneError');
    if (phone && !/^\d{10}$/.test(phone)) {
        e.preventDefault();
        err.textContent = 'Owner Contact Number must be 10 digits';
        err.style.display = 'block';
    } else {
        err.textContent = '';
        err.style.display = 'none';
    }
});
</script>
</body>
</html>

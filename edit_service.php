<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM services WHERE id=?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $stmt=$pdo->prepare("
        UPDATE services SET
        iom_zms=?, imul_no=?, bt_sn=?, installed_date=?, home_port=?,
        contact_number=?, current_status=?, feedback_to_call=?,
        service_done_date=?, comments=?, installation_checklist=?,
        updated_by=?, updated_at=NOW()
        WHERE id=?
    ");
    $stmt->execute([
        $_POST['iom_zms'], $_POST['imul_no'], $_POST['bt_sn'],
        $_POST['installed_date'], $_POST['home_port'],
        $_POST['contact_number'], $_POST['current_status'],
        $_POST['feedback_final'], $_POST['service_done_date'],
        $_POST['comments_final'],
        implode(',', $_POST['checklist'] ?? []),
        $_SESSION['user_id'], $id
    ]);
    echo "<script>alert('Updated');location='services.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Service</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="nav.css">
</head>
<body>
<?php require_once 'nav.php'; ?>
<div class="container"><div class="card">
<h1>Edit Service</h1>

<form method="POST">
<input name="imul_no" value="<?= $s['imul_no'] ?>">
<input name="bt_sn" value="<?= $s['bt_sn'] ?>">
<input name="contact_number" value="<?= $s['contact_number'] ?>">
<button class="btn-primary">Save</button>
</form>

</div></div>
</body>
</html>

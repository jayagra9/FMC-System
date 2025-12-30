<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$field = $_POST['field'] ?? '';

if (!in_array($field, ['departure_form','voyage'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid field']);
    exit();
}

$stmt = $pdo->prepare("SELECT $field FROM distress_vessels WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row[$field])) {
    echo json_encode(['success'=>false,'message'=>'File not found']);
    exit();
}

if (file_exists($row[$field])) unlink($row[$field]);

$pdo->prepare("UPDATE distress_vessels SET $field=NULL WHERE id=?")->execute([$id]);

echo json_encode(['success'=>true]);

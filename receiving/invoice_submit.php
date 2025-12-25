<?php
include '../config/db.php';
include '../includes/auth.php';
header('Content-Type: application/json');

$stmt = $conn->prepare("
INSERT INTO invoice_master
(InvoiceNo,InvoiceDate,SanctionOrderNo,SanctionDate,VendorName,AccountDetails,CreatedBy)
VALUES (?,?,?,?,?,?,?)
");

$stmt->execute([
$_POST['InvoiceNo'],
$_POST['InvoiceDate'],
$_POST['SanctionOrderNo'],
$_POST['SanctionDate'],
$_POST['VendorName'],
$_POST['AccountDetails'],
$_SESSION['user_id']
]);

echo json_encode(['status'=>'success']);

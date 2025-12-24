<?php
include '../config/db.php';
include '../includes/auth.php';

$stmt = $conn->prepare("
INSERT INTO invoice_master
(SanctionOrder,SanctionDate,InvoiceNumber,InvoiceDate,VendorName,AccountDetails,CreatedBy)
VALUES (?,?,?,?,?,?,?)
");

$stmt->execute([
$_POST['SanctionOrder'],
$_POST['SanctionDate'],
$_POST['InvoiceNumber'],
$_POST['InvoiceDate'],
$_POST['VendorName'],
$_POST['AccountDetails'],
$_SESSION['user_id']
]);

echo json_encode(['status'=>'success']);

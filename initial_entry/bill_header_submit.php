<?php
include '../config/db.php';
include '../includes/auth.php';

$conn->beginTransaction();

$stmt = $conn->prepare("
INSERT INTO bill_header (BillNumber,PFMSUniqueNumber,BillAmount,CreatedBy)
VALUES (?,?,?,?)
");
$stmt->execute([
$_POST['BillNumber'],
$_POST['PFMSUniqueNumber'],
$_POST['BillAmount'],
$_SESSION['user_id']
]);

$billId = $conn->lastInsertId();

$map = $conn->prepare("
INSERT INTO bill_invoice_map (BillId,InvoiceId) VALUES (?,?)
");

foreach($_POST['invoices'] as $inv){
    $map->execute([$billId,$inv]);
}

$conn->commit();

echo json_encode(['status'=>'success','bill_id'=>$billId]);

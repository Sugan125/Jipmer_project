<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$billNo   = trim($_POST['BillNumber'] ?? '');
$pfms     = trim($_POST['PFMSUniqueNumber'] ?? '');
$amount   = $_POST['BillAmount'] ?? 0;
$invoices = $_POST['InvoiceIds'] ?? [];

if($billNo=='' || $pfms=='' || $amount<=0){
    echo json_encode(['status'=>'error','message'=>'All fields required']);
    exit;
}

if(count($invoices)==0 || count($invoices)>6){
    echo json_encode(['status'=>'error','message'=>'Select 1 to 6 invoices']);
    exit;
}

try{
$conn->beginTransaction();

/* Insert bill */
$stmt = $conn->prepare("
    INSERT INTO bill_initial_entry
    (BillNumber, PFMSUniqueNumber, BillAmount, CreatedBy, CreatedIP)
    VALUES (?,?,?,?,?)
");
$stmt->execute([
    $billNo,
    $pfms,
    $amount,
    $_SESSION['user_id'],
    $_SERVER['REMOTE_ADDR']
]);

$initialId = $conn->lastInsertId();

/* Map invoices */
$stmt = $conn->prepare("
    INSERT INTO bill_initial_invoices (InitialBillId, InvoiceId)
    VALUES (?,?)
");

foreach($invoices as $inv){
    $stmt->execute([$initialId, $inv]);
}

$conn->commit();

echo json_encode(['status'=>'success','id'=>$initialId]);

}catch(Exception $e){
$conn->rollBack();
echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

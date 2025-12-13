<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(3);
header('Content-Type: application/json');

$billId = intval($_POST['bill_id'] ?? 0);
$txn = trim($_POST['transaction_no'] ?? '');
$batch = trim($_POST['batch_no'] ?? '');

if(!$billId || !$txn || !$batch){
    echo json_encode(['status'=>'error','message'=>'All fields required']);
    exit;
}

// Prevent duplicate transaction for same bill
$chk = $conn->prepare("SELECT 1 FROM bill_transactions WHERE BillId=?");
$chk->execute([$billId]);
if($chk->fetch()){
    echo json_encode(['status'=>'error','message'=>'Transaction already exists for this bill']);
    exit;
}

// Insert transaction
$stmt = $conn->prepare("INSERT INTO bill_transactions
    (BillId, TransactionNo, BatchNo, CreatedBy, CreatedIP)
    VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$billId, $txn, $batch, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);

// Update bill status to 'Transaction Done'
$update = $conn->prepare("UPDATE bill_entry SET Status='Transaction Done' WHERE Id=?");
$update->execute([$billId]);

echo json_encode(['status'=>'success','message'=>'Transaction saved and bill status updated']);
exit;

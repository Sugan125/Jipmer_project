<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

if(!isset($_POST['id'])){
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$billId = intval($_POST['id']);

try{
    $conn->beginTransaction();

    /* Check if bill already moved to bill_entry */
    $chk = $conn->prepare("
        SELECT COUNT(*) FROM bill_entry WHERE BillInitialId = ?
    ");
    $chk->execute([$billId]);

    if($chk->fetchColumn() > 0){
        throw new Exception("Bill already processed. Cannot delete.");
    }

    /* Delete invoice mapping first */
    $conn->prepare("
        DELETE FROM bill_invoice_map WHERE BillInitialId = ?
    ")->execute([$billId]);

    /* Delete bill */
    $conn->prepare("
        DELETE FROM bill_initial_entry WHERE Id = ?
    ")->execute([$billId]);

    $conn->commit();

    echo json_encode([
        'status'=>'success',
        'message'=>'Bill deleted successfully'
    ]);

}catch(Exception $e){
    $conn->rollBack();
    echo json_encode([
        'status'=>'error',
        'message'=>$e->getMessage()
    ]);
}

<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

if(!isset($_POST['po_id'])){
    echo json_encode(['status'=>'error','message'=>'PO ID missing']);
    exit;
}

$poId = (int)$_POST['po_id'];

try{
    $conn->beginTransaction();

    // Delete all sanctions for this PO
    $stmt1 = $conn->prepare("DELETE FROM sanction_order_master WHERE POId = ?");
    $stmt1->execute([$poId]);

    // Delete PO
    $stmt2 = $conn->prepare("DELETE FROM po_master WHERE Id = ?");
    $stmt2->execute([$poId]);

    $conn->commit();

    echo json_encode(['status'=>'success']);

}catch(Exception $e){
    $conn->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

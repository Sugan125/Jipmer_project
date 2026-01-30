<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try{
    $poId = (int)($_POST['po_id'] ?? 0);
    $mode = trim($_POST['mode'] ?? '');

    if($poId <= 0) throw new Exception('Invalid PO ID');
    if(!in_array($mode, ['edit','view'], true)) throw new Exception('Invalid mode');

    // verify PO exists
    $st = $conn->prepare("SELECT COUNT(*) FROM po_master WHERE Id = ?");
    $st->execute([$poId]);
    if((int)$st->fetchColumn() <= 0) throw new Exception('PO not found');

    $_SESSION['po_context_id'] = $poId;
    $_SESSION['po_context_mode'] = $mode;

    echo json_encode(['status'=>'success']);
}catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';

$type  = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');
$response = ['duplicate'=>false];

if($value===''){
    echo json_encode($response);
    exit;
}

if($type === 'po'){
    $stmt = $conn->prepare("SELECT COUNT(*) FROM po_master WHERE POOrderNo = ?");
    $stmt->execute([$value]);
    if($stmt->fetchColumn() > 0){
        $response['duplicate'] = true;
    }
}

if($type === 'sanction'){
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sanction_order_master WHERE SanctionOrderNo = ?");
    $stmt->execute([$value]);
    if($stmt->fetchColumn() > 0){
        $response['duplicate'] = true;
    }
}

echo json_encode($response);

<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);

if (!$type || !$id) {
    echo json_encode(['status'=>'error','message'=>'Invalid input']);
    exit;
}

try {
    if($type == 'finyear'){
        $stmt = $conn->prepare("DELETE FROM fin_year_master WHERE Id = ?");
        $stmt->execute([$id]);
        $msg = 'Financial Year deleted';
    }
    elseif($type == 'hoa'){
        $stmt = $conn->prepare("DELETE FROM hoa_master WHERE Id = ?");
        $stmt->execute([$id]);
        $msg = 'HOA deleted';
    }
    else{
        echo json_encode(['status'=>'error','message'=>'Unknown type']);
        exit;
    }

    echo json_encode(['status'=>'success','message'=>$msg]);
} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

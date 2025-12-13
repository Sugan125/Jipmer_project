<?php
session_start();
include '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 4) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if(!$id){ echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }

// optional: prevent deleting yourself
if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id){
    echo json_encode(['status'=>'error','message'=>'You cannot delete your own account']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM employee_master WHERE Id = ?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success','message'=>'Employee deleted']);
    exit;
} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>'DB error: '.$e->getMessage()]);
    exit;
}

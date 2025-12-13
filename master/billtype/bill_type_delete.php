<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['status'=>'error','message'=>'Invalid ID']);
    exit;
}

try {
    // Delete the row completely
    $stmt = $conn->prepare("DELETE FROM bill_type_master WHERE Id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status'=>'success','message'=>'Bill Type deleted permanently']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

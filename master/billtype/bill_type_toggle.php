<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if(!$id){ echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }

$stmt = $conn->prepare("UPDATE bill_type_master SET IsActive = CASE WHEN IsActive=1 THEN 0 ELSE 1 END, UpdatedBy=?, UpdatedDate=GETDATE() WHERE Id=?");
$stmt->execute([$_SESSION['user_id'], $id]);

echo json_encode(['status'=>'success','message'=>'Status updated']);

<?php
session_start();
include '../../config/db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if(!$id){ echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }

$stmt = $conn->prepare("SELECT Id,EmpCode, EmployeeName, Username, RoleId FROM employee_master WHERE Id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ echo json_encode(['status'=>'error','message'=>'Employee not found']); exit; }

echo json_encode(['status'=>'success','data'=>$row]);
exit;

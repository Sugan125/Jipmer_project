<?php
include '../../config/db.php';
include '../../includes/auth.php';

$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if(!$id){ echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }

$stmt = $conn->prepare("UPDATE bill_type_master SET IsActive = CASE WHEN IsActive=1 THEN 0 ELSE 1 END, UpdatedBy=?, UpdatedDate=GETDATE() WHERE Id=?");
$stmt->execute([$_SESSION['user_id'], $id]);

echo json_encode(['status'=>'success','message'=>'Status updated']);

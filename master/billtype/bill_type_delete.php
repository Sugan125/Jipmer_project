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

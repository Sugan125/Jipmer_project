<?php
include '../config/db.php';

$roleId = $_POST['roleId'];
$menus  = $_POST['menus'] ?? [];

$conn->prepare("DELETE FROM role_menu_permission WHERE RoleId=?")
     ->execute([$roleId]);

$stmt = $conn->prepare(
    "INSERT INTO role_menu_permission (RoleId, MenuId) VALUES (?, ?)"
);

foreach ($menus as $menuId) {
    $stmt->execute([$roleId, $menuId]);
}

echo json_encode(['status'=>'success','message'=>'Menu access updated']);

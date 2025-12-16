<?php
session_start();
include '../config/db.php';



$action = $_POST['action'] ?? '';

/* ADD / EDIT */
if ($action == 'save') {
    $id   = $_POST['RoleId'] ?? '';
    $name = trim($_POST['RoleName']);

    if ($id) {
        $stmt = $conn->prepare("UPDATE roles SET RoleName=? WHERE RoleId=?");
        $stmt->execute([$name,$id]);
        echo json_encode(['status'=>'success','message'=>'Role updated']);
    } else {
        $stmt = $conn->prepare("INSERT INTO roles(RoleName) VALUES(?)");
        $stmt->execute([$name]);
        echo json_encode(['status'=>'success','message'=>'Role added']);
    }
    exit;
}

/* DELETE */
if ($action == 'delete') {
    $id = intval($_POST['RoleId']);

    // Prevent delete if assigned
    $check = $conn->prepare("
        SELECT COUNT(*) FROM role_menu_permission WHERE RoleId=?
    ");
    $check->execute([$id]);

    if ($check->fetchColumn() > 0) {
        echo json_encode([
            'status'=>'error',
            'message'=>'Role assigned to menus. Cannot delete.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM roles WHERE RoleId=?");
    $stmt->execute([$id]);

    echo json_encode(['status'=>'success','message'=>'Role deleted']);
    exit;
}

<?php
include '../config/db.php';

$roleId = $_GET['roleId'];

$stmt = $conn->prepare(
    "SELECT MenuId FROM role_menu_permission WHERE RoleId = ?"
);
$stmt->execute([$roleId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));

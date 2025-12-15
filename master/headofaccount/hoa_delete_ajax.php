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


if (!isset($_POST['id'])) {
    echo "error";
    exit;
}

$id = intval($_POST['id']);

$stmt = $conn->prepare("DELETE FROM head_of_account_master WHERE Id = ?");
if ($stmt->execute([$id])) {
    echo "success";
} else {
    echo "error";
}

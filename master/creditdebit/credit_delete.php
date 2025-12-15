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

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) exit;

try {
    $stmt = $conn->prepare("DELETE FROM account_credit_master WHERE Id=?");
    $stmt->execute([$id]);
} catch (PDOException $e) {
    exit("Error deleting credit: ".$e->getMessage());
}

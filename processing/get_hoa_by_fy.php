<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);


$fy = $_GET['fy'] ?? '';
if($fy === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT Id, FullHOA FROM head_of_account_master WHERE FinancialYear = ? ORDER BY FullHOA");
$stmt->execute([$fy]);
$hoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($hoas);

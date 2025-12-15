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


if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['DebitName']);
    $status = isset($_POST['Status']) && $_POST['Status'] == 1 ? 1 : 0;
    if($name !== ''){
        $stmt = $conn->prepare("INSERT INTO account_debit_master (DebitName, Status, CreatedBy) VALUES (?, ?, ?)");
        $stmt->execute([$name, $status, $_SESSION['user_id']]);
    }
}

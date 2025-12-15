<?php
session_start();
include '../config/db.php'; // Your PDO connection

$roleId = $_SESSION['RoleId'];
$employeeId = $_SESSION['Id']; // employee_master.Id

// 1. Check if employee has specific menu permissions
$stmt = $conn->prepare("
    SELECT m.MenuName, m.PageUrl 
    FROM menu_master m
    INNER JOIN employee_menu_permission emp ON m.MenuId = emp.MenuId
    WHERE emp.EmployeeId = ?
    UNION
    SELECT m.MenuName, m.PageUrl
    FROM menu_master m
    INNER JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ?
");
$stmt->execute([$employeeId, $roleId]);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <?php foreach($menus as $menu): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title"><?= $menu['MenuName'] ?></h5>
                    <a href="<?= $menu['PageUrl'] ?>" class="btn btn-primary">Open</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

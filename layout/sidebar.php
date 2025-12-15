<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../config/db.php';

$roleId = $_SESSION['role'] ?? 0;

$stmt = $conn->prepare("
    SELECT m.MenuName, m.PageUrl, m.IconClass
    FROM menu_master m
    INNER JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND rmp.Status = 1 AND m.Status = 1
    ORDER BY m.SortOrder
");
$stmt->execute([$roleId]);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<head>
    <meta charset="UTF-8">
    <title>Menu Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/sweetalert2.all.min.js"></script>
</head>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <?php foreach ($menus as $menu): ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?= BASE_URL ?>/<?= $menu['PageUrl'] ?>">
                    <i class="<?= $menu['IconClass'] ?>"></i>
                    <?= htmlspecialchars($menu['MenuName']) ?>
                </a>
            </li>
            <?php endforeach; ?>
           
        </ul>
    </div>
</nav>

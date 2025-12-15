<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include '../layout/topbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include '../layout/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">
            <h4>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h4>
            <p class="text-muted">Use the menu on the left to continue.</p>
        </main>
    </div>
</div>

<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

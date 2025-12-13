<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role'] != 2){
    header("Location: ../auth/login.php");
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Dashboard</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="../css/all.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<style>
.card-hover:hover {
    transform: translateY(-5px);
    transition: 0.3s;
}
</style>
</head>
<body>

<?php include '../header/header_receiving.php'; ?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">Audit Section Dashboard</h3>
    <div class="row g-4 justify-content-center">

        <!-- Process Bills -->
        <div class="col-md-6 col-lg-4 d-flex">
            <a href="../processing/process_list.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                    <i class="fas fa-tasks text-primary mb-3" style="font-size:50px;"></i>
                    <h5 class="card-title fw-bold">Process Bills</h5>
                </div>
            </a>
        </div>

        <!-- Logout
        <div class="col-md-6 col-lg-4 d-flex">
            <a href="../auth/logout.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                    <i class="fas fa-sign-out-alt text-danger mb-3" style="font-size:50px;"></i>
                    <h5 class="card-title fw-bold">Logout</h5>
                </div>
            </a>
        </div> -->

    </div>
</div>

<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

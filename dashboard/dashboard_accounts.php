<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role'] != 3){
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
<title>Accounts Dashboard</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="../css/all.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php include '../header/header_accounts.php'; ?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">Accounts Section Dashboard</h3>
    <div class="row g-4 justify-content-center">

            <!-- Transaction Entry -->
        <div class="col-md-6 col-lg-4 d-flex">
            <a href="../accounts/transaction_add.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                    <i class="fas fa-receipt text-info mb-3" style="font-size:50px;"></i>
                    <h5 class="card-title fw-bold">Transaction / Batch Entry</h5>
                </div>
            </a>
        </div>
        <!-- Add Voucher -->
        <div class="col-md-6 col-lg-4 d-flex">
            <a href="../accounts/voucher_add.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                    <i class="fas fa-file-invoice-dollar text-primary mb-3" style="font-size:50px;"></i>
                    <h5 class="card-title fw-bold">Add Voucher</h5>
                </div>
            </a>
        </div>

        <!-- PFMS Entry (commented for now) -->
        <!--
        <div class="col-md-6 col-lg-4 d-flex">
            <a href="../accounts/accounts_pending.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                    <i class="fas fa-list-alt text-success mb-3" style="font-size:50px;"></i>
                    <h5 class="card-title fw-bold">PFMS Entry</h5>
                </div>
            </a>
        </div>
        -->

        <!-- Logout -->
        <!-- <div class="col-md-6 col-lg-4 d-flex">
            <a href="../auth/logout.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                    <i class="fas fa-sign-out-alt text-danger mb-3" style="font-size:50px;"></i>
                    <h5 class="card-title fw-bold">Logout</h5>
                </div>
            </a>
        </div> -->

    </div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>

</body>
</html>

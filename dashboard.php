<?php
include("config/db.php");

// Start session safely
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JIPMER Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    .card-hover { transition: 0.3s; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
    .card-title { font-size: 1.5rem; }
</style>
</head>
<body class="bg-light">

<?php include 'header/header.php'; ?>

<div class="container mt-4">
    <h4>WELCOME, <span class="text-primary"><?= ucfirst(htmlspecialchars($username)) ?></span></h4>

    <div class="row g-4 mt-4 justify-content-center">

        <!-- Bill Entry Card -->
        <div class="col-md-8 col-lg-5 d-flex">
            <a href="bill_entry/bill_entry.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-5">
                    <i class="fas fa-file-invoice text-primary mb-4" style="font-size:60px;"></i>
                    <h4 class="card-title fw-bold">Bill Entry</h4>
                </div>
            </a>
        </div>

        <!-- Voucher Entry Card -->
        <div class="col-md-8 col-lg-5 d-flex">
            <a href="voucher_entry/voucher_entry.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-5">
                    <i class="fas fa-file-alt text-success mb-4" style="font-size:60px;"></i>
                    <h4 class="card-title fw-bold">Voucher Entry</h4>
                </div>
            </a>
        </div>

        <!-- Manage Bills Card -->
        <div class="col-md-8 col-lg-5 d-flex">
            <a href="bill_entry/manage_bills.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-5">
                    <i class="fas fa-list text-warning mb-4" style="font-size:60px;"></i>
                    <h4 class="card-title fw-bold">Manage Bills</h4>
                </div>
            </a>
        </div>

        <!-- Manage Vouchers Card -->
        <div class="col-md-8 col-lg-5 d-flex">
            <a href="voucher_entry/manage_vouchers.php" class="text-decoration-none flex-fill">
                <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-5">
                    <i class="fas fa-receipt text-danger mb-4" style="font-size:60px;"></i>
                    <h4 class="card-title fw-bold">Manage Vouchers</h4>
                </div>
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

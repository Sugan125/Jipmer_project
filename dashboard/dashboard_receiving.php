<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role'] != 1){
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
<title>Receiving Dashboard</title>
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="../css/all.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
</head>
<body>


<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">Bill Entry Section Dashboard</h3>
    <div class="row g-4 justify-content-center">

    <!-- Add Bill Entry -->
    <div class="col-md-6 col-lg-4 d-flex">
        <a href="../receiving/bill_entry_add.php" class="text-decoration-none flex-fill">
            <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                <i class="fas fa-file-invoice text-primary mb-3" style="font-size:50px;"></i>
                <h5 class="card-title fw-bold">Add Bill Entry </h5>
            </div>
        </a>
    </div>

    <!-- Returned Bills -->
    <div class="col-md-6 col-lg-4 d-flex">
        <a href="../receiving/returned_bills.php" class="text-decoration-none flex-fill">
            <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                <i class="fas fa-undo text-danger mb-3" style="font-size:50px;"></i>
                <h5 class="card-title fw-bold">Returned Bills</h5>
            </div>
        </a>
    </div>

    
    <!-- View Bills -->
    <div class="col-md-6 col-lg-4 d-flex">
        <a href="../receiving/bill_entry_list.php" class="text-decoration-none flex-fill">
            <div class="card card-hover text-center shadow-lg rounded-4 h-100 p-4">
                <i class="fas fa-list text-success mb-3" style="font-size:50px;"></i>
                <h5 class="card-title fw-bold">View Bills</h5>
            </div>
        </a>
    </div>

    
</div>

</div>

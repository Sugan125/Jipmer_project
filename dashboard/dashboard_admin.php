<?php
session_start();

// Only Admin (Role = 4) can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 4) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<style>
    .card-custom {
        min-height: 180px; /* ensures uniform height */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .card-body .btn {
        margin-bottom: 5px;
    }
</style>

</head>

<body class="bg-light">
<?php include '../header/header_admin.php'; ?>

<div class="container mt-4">
    <h3 class="text-center">Admin Dashboard</h3>
    <p class="text-center text-muted">Welcome, <?= $_SESSION['username']; ?></p>

    <div class="row mt-4 justify-content-center">

        <!-- Employee Master -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">👨‍💼 Employee Master</h5>
                    <p class="text-muted">Manage Employee Login, Roles & Credentials</p>
                    <a href="../master/employee/employee_add.php" class="btn btn-primary btn-sm w-100">➕ Add Employee</a>
                    <a href="../master/employee/employee_list.php" class="btn btn-secondary btn-sm w-100">View Employees</a>
                </div>
            </div>
        </div>

        <!-- Head of Account Master -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">🏛️ Head of Account Master</h5>
                    <p class="text-muted">Create & Maintain HOA Details</p>
                    <a href="../master/headofaccount/hoa_add.php" class="btn btn-primary btn-sm w-100">➕ Add HOA</a>
                    <a href="../master/headofaccount/hoa_list.php" class="btn btn-secondary btn-sm w-100">View HOAs</a>
                </div>
            </div>
        </div>

        <!-- Bill Type Master -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">🧾 Bill Type Master</h5>
                    <p class="text-muted mb-5">Manage Bill Types (Company / Salary)</p>
                    <a href="../master/billtype/bill_type_master.php" class="btn  btn-primary btn-sm w-100">Manage Bill Types</a>
                </div>
            </div>
        </div>

        <!-- Financial Year Master -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">📅 Financial Year Master</h5>
                    <p class="text-muted mb-5">Manage Financial Years</p>
                    <a href="../master/finyear/fin_year_master.php" class="btn  btn-primary btn-sm w-100">Manage Fin Years</a>
                </div>
            </div>
        </div>

        <!-- Credit To Master -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">💰 Account Credit To</h5>
                    <p class="text-muted mb-5">Manage Credit options (Vendor / GIA / Income Tax)</p>
                    <a href="../master/creditdebit/credit_master.php" class="btn  btn-primary btn-sm w-100">Manage Credits</a>
                </div>
            </div>
        </div>

        <!-- Debit From Master -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">💳 Account Debit From</h5>
                    <p class="text-muted mb-5">Manage Debit options (PFMS / GIA / Income Tax)</p>
                    <a href="../master/creditdebit/debit_master.php" class="btn  btn-primary btn-sm w-100">Manage Debits</a>
                </div>
            </div>
        </div>

        <!-- Credit Report -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-lg border-0 card-custom">
                <div class="card-body">
                    <h5 class="card-title">📊 Credit Report</h5>
                    <p class="text-muted mb-5">View all bills credited to GIA and Income Tax</p>
                    <a href="../reports/report_credit_to.php" class="btn btn-primary btn-sm w-100">View Report</a>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>

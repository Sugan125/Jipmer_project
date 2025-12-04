<?php
include '../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }

$vouchers = $conn->query("
    SELECT v.Id, b.BillNo, v.VoucherNumber, v.CreatedBy
    FROM voucher v 
    LEFT JOIN bill_entry b ON v.BillNumber = b.Id
    ORDER BY v.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Vouchers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../header/header.php'; ?>

<div class="container mt-5">
    <h3>All Vouchers</h3>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Bill Number</th>
                    <th>Voucher Number</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                foreach($vouchers as $v): ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td><?= htmlspecialchars($v['BillNo']) ?></td>
                    <td><?= htmlspecialchars($v['VoucherNumber']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($v['CreatedBy'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

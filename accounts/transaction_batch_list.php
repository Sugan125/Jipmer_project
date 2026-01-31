<?php
include '../config/db.php';
include '../includes/auth.php';

$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");


/* ===== FETCH BATCHES ===== */
$batches = $conn->query("
    SELECT
        bt.BatchNo,
        bt.VoucherNo,
        COUNT(bt.BillId) AS BillCount,
        SUM(im.NetPayable) AS TotalAmount,
        MAX(bt.CreatedDate) AS CreatedDate
    FROM bill_transactions bt
    INNER JOIN bill_entry b ON b.Id = bt.BillId
    INNER JOIN bill_initial_entry bi ON bi.Id = b.BillInitialId
    INNER JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    INNER JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY bt.BatchNo, bt.VoucherNo
    ORDER BY CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Batched Transactions</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content" style="margin-left:240px;padding:90px 20px">

<h4 class="text-center mb-4">
<i class="fa fa-boxes-stacked"></i> Batched Transactions
</h4>

<div class="table-responsive bg-white shadow rounded p-3">

<table id="batchTable" class="table table-bordered table-striped text-center">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Batch No</th>
    <th>Voucher No</th>
    <th>No. of Bills</th>
    <th>Total Amount</th>
    <th>Created Date</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php $i=1; foreach($batches as $b): ?>
<tr>
    <td><?= $i++ ?></td>
    <td class="fw-bold"><?= htmlspecialchars($b['BatchNo']) ?></td>
    <td class="text-primary fw-bold"><?= htmlspecialchars($b['VoucherNo']) ?></td>
    <td><?= $b['BillCount'] ?></td>
    <td class="text-success fw-bold"><?= number_format($b['TotalAmount'],2) ?></td>
    <td><?= $b['CreatedDate'] ?></td>
    <td>
        <a href="transaction_batch_view.php?batch=<?= urlencode($b['BatchNo']) ?>&voucher=<?= urlencode($b['VoucherNo']) ?>"
           class="btn btn-sm btn-primary">
           <i class="fa fa-eye"></i> View Bills
        </a>
        <a href="transaction_batch_full_view.php?batch=<?= urlencode($b['BatchNo']) ?>&voucher=<?= urlencode($b['VoucherNo']) ?>"
   class="btn btn-sm btn-primary">
   <i class="fa fa-eye"></i> Full Bill View
</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>

<script>
$('#batchTable').DataTable({
    pageLength:10,
    order:[[0,'desc']]
});
</script>

</body>
</html>

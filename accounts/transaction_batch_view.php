<?php
include '../config/db.php';
include '../includes/auth.php';

$batch   = $_GET['batch'] ?? '';
$voucher = $_GET['voucher'] ?? '';

$stmt = $conn->prepare("
    SELECT
        bi.BillNumber,
        im.VendorName,
        im.InvoiceNo,
        im.InvoiceDate,
        im.NetPayable
    FROM bill_transactions bt
    INNER JOIN bill_entry b ON b.Id = bt.BillId
    INNER JOIN bill_initial_entry bi ON bi.Id = b.BillInitialId
    INNER JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    INNER JOIN invoice_master im ON im.Id = bim.InvoiceId
    WHERE bt.BatchNo = ? AND bt.VoucherNo = ?
");
$stmt->execute([$batch, $voucher]);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Batch Bills</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

<style>
.page-content{margin-left:240px;padding:90px 25px}
.card{max-width:1100px;margin:auto}
.table th,.table td{vertical-align:middle;text-align:center}
.amount{text-align:right;font-weight:600}
tfoot td{font-weight:700;background:#f1f3f5}
.badge-info{background:#eef4ff;color:#0d6efd}
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<div class="card shadow p-4">

<!-- ===== HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary mb-0">
        <i class="fa fa-folder-open"></i> Batched Bills
    </h4>
    <a href="transaction_batch_list.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Back
    </a>
</div>

<!-- ===== BATCH INFO ===== -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="small text-muted">Batch Number</div>
        <div class="fw-bold fs-6"><?= htmlspecialchars($batch) ?></div>
    </div>
    <div class="col-md-4">
        <div class="small text-muted">Voucher Number</div>
        <div class="fw-bold fs-6 text-primary"><?= htmlspecialchars($voucher) ?></div>
    </div>
    <div class="col-md-4">
        <div class="small text-muted">Total Bills</div>
        <div class="fw-bold fs-6"><?= count($bills) ?></div>
    </div>
</div>

<!-- ===== TABLE ===== -->
<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Bill No</th>
    <th>Vendor Name</th>
    <th>Invoice No</th>
    <th>Invoice Date</th>
    <th>Net Amount</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;
$total = 0;
foreach($bills as $b):
    $total += $b['NetPayable'];
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($b['BillNumber']) ?></td>
    <td><?= htmlspecialchars($b['VendorName']) ?></td>
    <td><?= htmlspecialchars($b['InvoiceNo']) ?></td>
    <td><?= $b['InvoiceDate'] ?></td>
    <td class="amount text-success"><?= number_format($b['NetPayable'],2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot>
<tr>
    <td colspan="5" class="text-end">TOTAL</td>
    <td class="amount text-success"><?= number_format($total,2) ?></td>
</tr>
</tfoot>
</table>
</div>

</div>
</div>

</body>
</html>

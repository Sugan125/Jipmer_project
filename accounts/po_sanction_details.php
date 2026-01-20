<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

if(!isset($_GET['po_id'])){
    die('PO ID Missing');
}

$poId = (int)$_GET['po_id'];

/* ================= FETCH PO ================= */
$poStmt = $conn->prepare("
    SELECT *
    FROM po_master
    WHERE Id = ?
");
$poStmt->execute([$poId]);
$po = $poStmt->fetch();

if(!$po){
    die('Invalid PO');
}

/* ================= FETCH BANK DETAILS ================= */
$bankStmt = $conn->prepare("
    SELECT *
    FROM po_bank_details
    WHERE po_id = ? AND is_active = 1
");
$bankStmt->execute([$poId]);
$bank = $bankStmt->fetch(PDO::FETCH_ASSOC);

/* ================= FETCH SANCTIONS ================= */
$sanStmt = $conn->prepare("
    SELECT *
    FROM sanction_order_master
    WHERE POId = ?
    ORDER BY SanctionDate
");
$sanStmt->execute([$poId]);
$sanctions = $sanStmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>PO & Sanction Details</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1100px;margin:auto;}
.section-card{
    border:1px solid #dee2e6;
    border-radius:8px;
    padding:20px;
    margin-bottom:25px;
    background:#f9f9f9;
}
.section-title{
    font-weight:600;
    color:#0d6efd;
    margin-bottom:15px;
}
.label-title{
    font-size:13px;
    color:#6c757d;
}
.value-text{
    font-weight:600;
}
.table th,.table td{
    vertical-align:middle;
}
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">

<h4 class="text-primary mb-4">
<i class="fa fa-eye"></i> PO & Sanction Order Details
</h4>

<!-- ================= PO DETAILS ================= -->
<div class="section-card">
<div class="section-title">Purchase Order Details</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="label-title">PO Number</div>
        <div class="value-text"><?= htmlspecialchars($po['POOrderNo']) ?></div>
    </div>
    <div class="col-md-3">
        <div class="label-title">PO Date</div>
        <div class="value-text"><?= htmlspecialchars($po['POOrderDate']) ?></div>
    </div>
    <div class="col-md-3">
        <div class="label-title">PO Amount</div>
        <div class="value-text text-end"><?= number_format($po['POAmount'],2) ?></div>
    </div>
    <div class="col-md-3">
        <div class="label-title">PO Net Amount</div>
        <div class="value-text text-success text-end">
            <?= number_format($po['PONetAmount'],2) ?>
        </div>
    </div>

    <div class="col-md-3">
        <div class="label-title">GST %</div>
        <div class="value-text"><?= $po['POGSTPercent'] ?> %</div>
    </div>
    <div class="col-md-3">
        <div class="label-title">IT %</div>
        <div class="value-text"><?= $po['POITPercent'] ?> %</div>
    </div>
    <div class="col-md-3">
        <div class="label-title">Created Date</div>
        <div class="value-text"><?= $po['CreatedDate'] ?></div>
    </div>
</div>
</div>
<!-- ================= BANK / ACCOUNT DETAILS ================= -->
<div class="section-card">
<div class="section-title">Bank & Account Details</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="label-title">PAN Number</div>
        <div class="value-text">
            <?= htmlspecialchars($bank['pan_number'] ?? '-') ?>
        </div>
    </div>

    <div class="col-md-3">
        <div class="label-title">PFMS Number</div>
        <div class="value-text">
            <?= htmlspecialchars($bank['pfms_number'] ?? '-') ?>
        </div>
    </div>

    <div class="col-md-3">
        <div class="label-title">Bank Name</div>
        <div class="value-text">
            <?= htmlspecialchars($bank['bank_name'] ?? '-') ?>
        </div>
    </div>

    <div class="col-md-3">
        <div class="label-title">IFSC</div>
        <div class="value-text">
            <?= htmlspecialchars($bank['ifsc'] ?? '-') ?>
        </div>
    </div>

    <div class="col-md-3">
        <div class="label-title">Account Number</div>
        <div class="value-text">
            <?= htmlspecialchars($bank['account_number'] ?? '-') ?>
        </div>
    </div>
</div>
</div>

<!-- ================= SANCTION DETAILS ================= -->
<div class="section-card">
<div class="section-title">Sanction Order Details</div>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>Sanction No</th>
    <th>Date</th>
    <th class="text-end">Amount</th>
    <th class="text-end">GST</th>
    <th class="text-end">IT</th>
    <th class="text-end">Net Amount</th>
</tr>
</thead>
<tbody>
<?php
$totalAmt = $totalGst = $totalIt = $totalNet = 0;
$i = 1;
foreach($sanctions as $s):
    $totalAmt += $s['SanctionAmount'];
    $totalGst += $s['GSTAmount'];
    $totalIt  += $s['ITAmount'];
    $totalNet += $s['SanctionNetAmount'];
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($s['SanctionOrderNo']) ?></td>
    <td><?= htmlspecialchars($s['SanctionDate']) ?></td>
    <td class="text-end"><?= number_format($s['SanctionAmount'],2) ?></td>
    <td class="text-end"><?= number_format($s['GSTAmount'],2) ?></td>
    <td class="text-end"><?= number_format($s['ITAmount'],2) ?></td>
    <td class="text-end fw-bold text-success">
        <?= number_format($s['SanctionNetAmount'],2) ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot class="table-light fw-bold">
<tr>
    <td colspan="3" class="text-end">TOTAL</td>
    <td class="text-end"><?= number_format($totalAmt,2) ?></td>
    <td class="text-end"><?= number_format($totalGst,2) ?></td>
    <td class="text-end"><?= number_format($totalIt,2) ?></td>
    <td class="text-end text-success"><?= number_format($totalNet,2) ?></td>
</tr>
</tfoot>
</table>
</div>
</div>

<div class="text-end">
<a href="po_sanction_list.php" class="btn btn-secondary">
<i class="fa fa-arrow-left"></i> Back
</a>
<a href="po_sanction_entry_edit.php?po_id=<?= $poId ?>" class="btn btn-primary">
<i class="fa fa-edit"></i> Edit
</a>
</div>

</div>
</div>

</body>
</html>

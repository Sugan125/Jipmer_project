<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

$poId = (int)($_SESSION['po_context_id'] ?? 0);
if($poId <= 0) die('PO ID Missing in session. Go back to list.');

/* PO */
$poStmt = $conn->prepare("SELECT * FROM po_master WHERE Id = ?");
$poStmt->execute([$poId]);
$po = $poStmt->fetch(PDO::FETCH_ASSOC);
if(!$po) die('Invalid PO');

/* BANK */
$bankStmt = $conn->prepare("SELECT TOP 1 * FROM po_bank_details WHERE po_id = ? ORDER BY Id DESC");
$bankStmt->execute([$poId]);
$bank = $bankStmt->fetch(PDO::FETCH_ASSOC);

/* ITEMS */
$itemStmt = $conn->prepare("SELECT * FROM po_items WHERE POId = ? ORDER BY Id ASC");
$itemStmt->execute([$poId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

/* SANCTIONS */
$sanStmt = $conn->prepare("SELECT * FROM sanction_order_master WHERE POId = ? ORDER BY Id ASC");
$sanStmt->execute([$poId]);
$sanctions = $sanStmt->fetchAll(PDO::FETCH_ASSOC);

/* totals */
$totalGST = 0; $totalIT = 0; $totalSan = 0;
foreach($items as $it){ $totalGST += (float)$it['GSTAmount']; $totalIT += (float)$it['ITAmount']; }
foreach($sanctions as $s){ $totalSan += (float)$s['SanctionAmount']; }
$balance = ((float)$po['PONetAmount']) - $totalSan;
?>
<!DOCTYPE html>
<html>
<head>
<title>PO Details</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1100px;margin:auto;}
.section-card{border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:25px;background:#f9f9f9;}
.section-title{font-weight:600;color:#0d6efd;margin-bottom:15px;}
.table td,.table th{vertical-align:middle;}
</style>
</head>
<body class="bg-light">
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">

  <div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="text-primary mb-0"><i class="fa fa-eye"></i> PO Full Details</h4>
    <div>
      <button class="btn btn-primary" onclick="window.location.href='po_sanction_entry_edit.php'">
        <i class="fa fa-edit"></i> Edit
      </button>
      <a href="po_sanction_list.php" class="btn btn-secondary">Back</a>
    </div>
  </div>

  <div class="section-card">
    <div class="section-title">PO Summary</div>
    <div class="row g-3">
      <div class="col-md-3"><b>PO No:</b> <?= htmlspecialchars($po['POOrderNo']) ?></div>
      <div class="col-md-3"><b>PO Date:</b> <?= htmlspecialchars(substr($po['POOrderDate'],0,10)) ?></div>
      <div class="col-md-3"><b>GST No:</b> <?= htmlspecialchars($po['GSTNumber'] ?? '-') ?></div>
      <div class="col-md-3"><b>Created:</b> <?= htmlspecialchars($po['CreatedDate'] ?? '-') ?></div>

      <div class="col-md-3"><b>Base:</b> <?= number_format((float)$po['POAmount'],2) ?></div>
      <div class="col-md-3"><b>GST:</b> <?= number_format($totalGST,2) ?></div>
      <div class="col-md-3"><b>IT:</b> <?= number_format($totalIT,2) ?></div>
      <div class="col-md-3 text-success"><b>Net:</b> <?= number_format((float)$po['PONetAmount'],2) ?></div>

      <div class="col-md-3"><b>Sanction Total:</b> <?= number_format($totalSan,2) ?></div>
      <div class="col-md-3 <?= ($balance < 0 ? 'text-danger' : 'text-primary') ?>">
        <b>Balance (Net):</b> <?= number_format($balance,2) ?>
      </div>
    </div>
  </div>

  <div class="section-card">
    <div class="section-title">Bank Details</div>
    <div class="row g-3">
      <div class="col-md-3"><b>PAN:</b> <?= htmlspecialchars($bank['pan_number'] ?? '-') ?></div>
      <div class="col-md-3"><b>PFMS:</b> <?= htmlspecialchars($bank['pfms_number'] ?? '-') ?></div>
      <div class="col-md-3"><b>Bank:</b> <?= htmlspecialchars($bank['bank_name'] ?? '-') ?></div>
      <div class="col-md-3"><b>IFSC:</b> <?= htmlspecialchars($bank['ifsc'] ?? '-') ?></div>
      <div class="col-md-3"><b>Acc No:</b> <?= htmlspecialchars($bank['account_number'] ?? '-') ?></div>
    </div>
  </div>

  <div class="section-card">
    <div class="section-title">PO Items</div>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Item</th><th class="text-end">Amount</th>
            <th class="text-end">GST%</th><th class="text-end">GST Amt</th>
            <th class="text-end">IT%</th><th class="text-end">IT Amt</th>
            <th class="text-end">Net</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; foreach($items as $it): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($it['ItemName']) ?></td>
            <td class="text-end"><?= number_format((float)$it['ItemAmount'],2) ?></td>
            <td class="text-end"><?= number_format((float)$it['GSTPercent'],2) ?></td>
            <td class="text-end"><?= number_format((float)$it['GSTAmount'],2) ?></td>
            <td class="text-end"><?= number_format((float)$it['ITPercent'],2) ?></td>
            <td class="text-end"><?= number_format((float)$it['ITAmount'],2) ?></td>
            <td class="text-end fw-bold"><?= number_format((float)$it['NetAmount'],2) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($items)): ?>
          <tr><td colspan="8" class="text-center text-muted">No items</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="section-card">
    <div class="section-title">Sanction Orders</div>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Sanction No</th><th>Date</th><th class="text-end">Amount</th>
          </tr>
        </thead>
        <tbody>
        <?php $j=1; foreach($sanctions as $s): ?>
          <tr>
            <td><?= $j++ ?></td>
            <td><?= htmlspecialchars($s['SanctionOrderNo']) ?></td>
            <td><?= htmlspecialchars(substr($s['SanctionDate'],0,10)) ?></td>
            <td class="text-end"><?= number_format((float)$s['SanctionAmount'],2) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($sanctions)): ?>
          <tr><td colspan="4" class="text-center text-muted">No sanctions</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

</body>
</html>

<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

$invoiceId = (int)($_GET['id'] ?? 0);
if($invoiceId <= 0){
    exit('<div class="text-danger text-center">Invalid Invoice ID</div>');
}

function nf($v){ return number_format((float)($v ?? 0), 2); }

/* ================= INVOICE + BASIC MASTERS ================= */
$invStmt = $conn->prepare("
    SELECT 
        im.*,
        d.DeptName,
        bt.BillType,
        cr.CreditName,
        dr.DebitName,
        h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName AS HOA_NAME
    FROM invoice_master im
    LEFT JOIN dept_master d ON d.Id = im.DeptId
    LEFT JOIN bill_type_master bt ON bt.Id = im.BillTypeId
    LEFT JOIN account_credit_master cr ON cr.Id = im.CreditToId
    LEFT JOIN account_debit_master dr ON dr.Id = im.DebitFromId
    LEFT JOIN hoa_master h ON h.HoaId = im.HOAId
    WHERE im.Id = ?
");
$invStmt->execute([$invoiceId]);
$inv = $invStmt->fetch(PDO::FETCH_ASSOC);

if(!$inv){
    exit('<div class="text-danger text-center">Invoice not found</div>');
}

$poId = (int)($inv['POId'] ?? 0);

/* ================= PO MASTER ================= */
$po = [];
if($poId > 0){
    $poStmt = $conn->prepare("SELECT * FROM po_master WHERE Id=?");
    $poStmt->execute([$poId]);
    $po = $poStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* ================= PO ITEMS ================= */
$poItems = [];
if($poId > 0){
    $itStmt = $conn->prepare("
        SELECT 
            Id, ItemName,
            ISNULL(ItemAmount,0) AS ItemAmount,
            ISNULL(GSTPercent,0) AS GSTPercent,
            ISNULL(GSTAmount,0) AS GSTAmount,
            ISNULL(ITPercent,0) AS ITPercent,
            ISNULL(ITAmount,0) AS ITAmount,
            ISNULL(NetAmount,0) AS NetAmount
        FROM po_items
        WHERE POId=?
        ORDER BY Id ASC
    ");
    $itStmt->execute([$poId]);
    $poItems = $itStmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ================= PO BANK ================= */
$poBank = [];
if($poId > 0){
    $bkStmt = $conn->prepare("SELECT TOP 1 * FROM po_bank_details WHERE po_id=? AND is_active=1 ORDER BY id DESC");
    $bkStmt->execute([$poId]);
    $poBank = $bkStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* ================= SANCTIONS USED (invoice_sanction_map) ================= */
$sanStmt = $conn->prepare("
    SELECT 
      so.Id,
      so.SanctionOrderNo,
      so.SanctionDate,
      ISNULL(so.SanctionAmount,0) AS SanctionAmount,
      ISNULL(so.SanctionNetAmount,0) AS SanctionNetAmount,

      ISNULL(ism.SanctionBaseAmount,0) AS UsedBase,
      ISNULL(ism.GSTAmount,0)          AS UsedGST,
      ISNULL(ism.ITAmount,0)           AS UsedIT,
      ISNULL(ism.NetAmount,0)          AS UsedNet

    FROM invoice_sanction_map ism
    INNER JOIN sanction_order_master so ON so.Id = ism.SanctionId
    WHERE ism.InvoiceId = ?
    ORDER BY so.Id ASC
");
$sanStmt->execute([$invoiceId]);
$sanctions = $sanStmt->fetchAll(PDO::FETCH_ASSOC);

/* --- Total used per sanction across ALL invoices --- */
$sanTotals = [];
foreach($sanctions as $s){
    $sid = (int)$s['Id'];
    $tStmt = $conn->prepare("SELECT SUM(ISNULL(SanctionBaseAmount,0)) FROM invoice_sanction_map WHERE SanctionId=?");
    $tStmt->execute([$sid]);
    $sanTotals[$sid] = (float)($tStmt->fetchColumn() ?? 0);
}
?>

<style>
.detail-grid b{color:#0d6efd;}
.detail-box{background:#fbfcff;border:1px solid #eef2ff;border-radius:12px;padding:12px;}
.small-h{font-size:12px;color:#6c757d;font-weight:700;text-transform:uppercase;}
</style>

<div class="detail-box">

  <!-- INVOICE MAIN -->
  <div class="row g-2 detail-grid">
    <div class="col-md-3"><div class="small-h">Invoice No</div><b><?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></b></div>
    <div class="col-md-3"><div class="small-h">Invoice Date</div><b><?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?></b></div>
    <div class="col-md-6"><div class="small-h">Vendor</div><b><?= htmlspecialchars($inv['VendorName'] ?? '-') ?></b></div>

    <div class="col-md-4"><div class="small-h">Department</div><?= htmlspecialchars($inv['DeptName'] ?? '-') ?></div>
    <div class="col-md-8"><div class="small-h">HOA</div><?= htmlspecialchars($inv['HOA_NAME'] ?? '-') ?></div>

    <div class="col-md-4"><div class="small-h">Received From</div><?= htmlspecialchars($inv['ReceivedFromSection'] ?? '-') ?></div>
    <div class="col-md-4"><div class="small-h">Section DA</div><?= htmlspecialchars($inv['SectionDAName'] ?? '-') ?></div>
    <div class="col-md-4"><div class="small-h">PFMS</div><?= htmlspecialchars($inv['PFMSNumber'] ?? '-') ?></div>

    <div class="col-md-3"><div class="small-h">Amount</div>₹ <?= nf($inv['Amount']) ?></div>
    <div class="col-md-3"><div class="small-h">Total Amount</div>₹ <?= nf($inv['TotalAmount']) ?></div>
    <div class="col-md-3"><div class="small-h">TDS GST</div><span class="text-danger">₹ <?= nf($inv['TDSGSTAmount']) ?></span></div>
    <div class="col-md-3"><div class="small-h">TDS IT</div><span class="text-danger">₹ <?= nf($inv['TDSITAmount']) ?></span></div>

    <div class="col-md-3"><div class="small-h">Total TDS</div><span class="text-danger">₹ <?= nf(($inv['TDSGSTAmount'] ?? 0) + ($inv['TDSITAmount'] ?? 0)) ?></span></div>
    <div class="col-md-3"><div class="small-h">Net Payable</div><span class="text-success fw-bold">₹ <?= nf($inv['NetPayable']) ?></span></div>
    <div class="col-md-3"><div class="small-h">Credit To</div><?= htmlspecialchars($inv['CreditName'] ?? '-') ?></div>
    <div class="col-md-3"><div class="small-h">Debit From</div><?= htmlspecialchars($inv['DebitName'] ?? '-') ?></div>
  </div>

  <hr>

  <!-- SANCTIONS -->
  <div class="fw-bold text-primary mb-2"><i class="fa fa-stamp"></i> Sanctions</div>
  <div class="table-responsive">
    <table class="table table-bordered table-sm">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Sanction No</th>
          <th>Date</th>
          <th class="text-end">Sanction Amt</th>
          <th class="text-end">Used Base (This Invoice)</th>
          <th class="text-end">Total Used (All)</th>
          <th class="text-end">Balance</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($sanctions)): ?>
        <tr><td colspan="7" class="text-center text-muted">No sanction mapping found</td></tr>
      <?php else:
        $k=1; $tSan=0; $tUsed=0; $tAll=0; $tBal=0;
        foreach($sanctions as $s):
          $sid=(int)$s['Id'];
          $sanAmt=(float)$s['SanctionAmount'];
          $used=(float)$s['UsedBase'];
          $all =(float)($sanTotals[$sid] ?? 0);
          $bal =$sanAmt - $all;
          $tSan += $sanAmt; $tUsed += $used; $tAll += $all; $tBal += $bal;
      ?>
        <tr>
          <td><?= $k++ ?></td>
          <td class="fw-bold"><?= htmlspecialchars($s['SanctionOrderNo']) ?></td>
          <td><?= !empty($s['SanctionDate']) ? date('d-m-Y', strtotime($s['SanctionDate'])) : '-' ?></td>
          <td class="text-end"><?= nf($sanAmt) ?></td>
          <td class="text-end text-danger"><?= nf($used) ?></td>
          <td class="text-end"><?= nf($all) ?></td>
          <td class="text-end fw-bold <?= ($bal < 0 ? 'text-danger':'text-success') ?>"><?= nf($bal) ?></td>
        </tr>
      <?php endforeach; ?>
        <tr class="table-secondary fw-bold">
          <td colspan="3" class="text-end">TOTAL</td>
          <td class="text-end"><?= nf($tSan) ?></td>
          <td class="text-end text-danger"><?= nf($tUsed) ?></td>
          <td class="text-end"><?= nf($tAll) ?></td>
          <td class="text-end"><?= nf($tBal) ?></td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <hr>

  <!-- PO -->
  <div class="fw-bold text-primary mb-2"><i class="fa fa-file-contract"></i> PO Details</div>
  <?php if(empty($po)): ?>
    <div class="text-muted">No PO linked.</div>
  <?php else: ?>
    <div class="row g-2">
      <div class="col-md-3"><div class="small-h">PO No</div><?= htmlspecialchars($po['POOrderNo'] ?? '-') ?></div>
      <div class="col-md-3"><div class="small-h">PO Date</div><?= !empty($po['POOrderDate']) ? date('d-m-Y', strtotime($po['POOrderDate'])) : '-' ?></div>
      <div class="col-md-3"><div class="small-h">GST No</div><?= htmlspecialchars($po['GSTNumber'] ?? '-') ?></div>
      <div class="col-md-3"><div class="small-h">PO Base</div>₹ <?= nf($po['POAmount'] ?? 0) ?></div>

      <div class="col-md-3"><div class="small-h">PO GST Amt</div>₹ <?= nf($po['POGSTAmount'] ?? 0) ?></div>
      <div class="col-md-3"><div class="small-h">PO IT Amt</div>₹ <?= nf($po['POITAmount'] ?? 0) ?></div>
      <div class="col-md-3"><div class="small-h">PO Net</div><span class="text-success fw-bold">₹ <?= nf($po['PONetAmount'] ?? 0) ?></span></div>
    </div>

    <div class="mt-3 fw-bold">PO Items</div>
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Item</th>
            <th class="text-end">Amount</th>
            <th class="text-end">GST%</th><th class="text-end">GST</th>
            <th class="text-end">IT%</th><th class="text-end">IT</th>
            <th class="text-end">Net</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($poItems)): ?>
          <tr><td colspan="8" class="text-center text-muted">No PO items</td></tr>
        <?php else:
          $i=1; $tA=0;$tG=0;$tI=0;$tN=0;
          foreach($poItems as $it){
            $tA += (float)$it['ItemAmount'];
            $tG += (float)$it['GSTAmount'];
            $tI += (float)$it['ITAmount'];
            $tN += (float)$it['NetAmount'];
        ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($it['ItemName'] ?? '') ?></td>
            <td class="text-end"><?= nf($it['ItemAmount']) ?></td>
            <td class="text-end"><?= nf($it['GSTPercent']) ?></td>
            <td class="text-end"><?= nf($it['GSTAmount']) ?></td>
            <td class="text-end"><?= nf($it['ITPercent']) ?></td>
            <td class="text-end"><?= nf($it['ITAmount']) ?></td>
            <td class="text-end fw-bold"><?= nf($it['NetAmount']) ?></td>
          </tr>
        <?php } ?>
          <tr class="table-secondary fw-bold">
            <td colspan="2" class="text-end">TOTAL</td>
            <td class="text-end"><?= nf($tA) ?></td>
            <td></td>
            <td class="text-end"><?= nf($tG) ?></td>
            <td></td>
            <td class="text-end"><?= nf($tI) ?></td>
            <td class="text-end"><?= nf($tN) ?></td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <hr>

  <!-- BANK -->
  <div class="fw-bold text-primary mb-2"><i class="fa fa-university"></i> Bank Details</div>
  <div class="row g-2">
    <div class="col-md-4"><div class="small-h">PAN</div><?= htmlspecialchars($poBank['pan_number'] ?? ($inv['PanNumber'] ?? '-')) ?></div>
    <div class="col-md-4"><div class="small-h">PFMS</div><?= htmlspecialchars($poBank['pfms_number'] ?? ($inv['PFMSNumber'] ?? '-')) ?></div>
    <div class="col-md-4"><div class="small-h">Bank</div><?= htmlspecialchars($poBank['bank_name'] ?? ($inv['BankName'] ?? '-')) ?></div>
    <div class="col-md-4"><div class="small-h">IFSC</div><?= htmlspecialchars($poBank['ifsc'] ?? ($inv['IFSC'] ?? '-')) ?></div>
    <div class="col-md-4"><div class="small-h">Account</div><?= htmlspecialchars($poBank['account_number'] ?? ($inv['AccountNumber'] ?? '-')) ?></div>
  </div>

</div>

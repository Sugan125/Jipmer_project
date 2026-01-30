<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

$billId = (int)($_GET['id'] ?? 0);
if($billId <= 0){
    exit('<div class="text-center text-danger">Invalid Bill ID</div>');
}

function nf($v){ return number_format((float)($v ?? 0), 2); }

/* ======================= BILL HEADER ======================= */
$billStmt = $conn->prepare("
    SELECT 
      bi.Id,
      bi.BillNumber,
      bi.BillReceivedDate,
      bi.Status,
      bi.TotalAmount,
      bi.TotalGST,
      bi.TotalTDS,
      bi.GrossTotal,
      bi.NetTotal,
      MAX(im.ReceivedFromSection) AS ReceivedFromSection,
      MAX(e.EmployeeName) AS AllotedTo
    FROM bill_initial_entry bi
    LEFT JOIN bill_entry be ON be.BillInitialId = bi.Id
    LEFT JOIN employee_master e ON e.Id = be.AllotedDealingAsst
    LEFT JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    LEFT JOIN invoice_master im ON im.Id = bim.InvoiceId
    WHERE bi.Id = ?
    GROUP BY bi.Id, bi.BillNumber, bi.BillReceivedDate, bi.Status, bi.TotalAmount, bi.TotalGST, bi.TotalTDS, bi.GrossTotal, bi.NetTotal
");
$billStmt->execute([$billId]);
$bill = $billStmt->fetch(PDO::FETCH_ASSOC);

if(!$bill){
    exit('<div class="text-center text-danger">Bill not found</div>');
}

/* ======================= INVOICES IN BILL ======================= */
$invStmt = $conn->prepare("
    SELECT 
        im.*,
        d.DeptName,
        bt.BillType,
        cr.CreditName,
        dr.DebitName,
        h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName AS HOA_NAME
    FROM bill_invoice_map bim
    INNER JOIN invoice_master im ON im.Id = bim.InvoiceId
    LEFT JOIN dept_master d ON im.DeptId = d.Id
    LEFT JOIN bill_type_master bt ON im.BillTypeId = bt.Id
    LEFT JOIN account_credit_master cr ON im.CreditToId = cr.Id
    LEFT JOIN account_debit_master dr ON im.DebitFromId = dr.Id
    LEFT JOIN hoa_master h ON im.HOAId = h.HoaId
    WHERE bim.BillInitialId = ?
    ORDER BY im.Id DESC
");
$invStmt->execute([$billId]);
$invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($invoices)){
    exit('<div class="text-center text-muted py-3">No invoices attached to this bill.</div>');
}

/* ======================= BILL TOTALS FROM INVOICES ======================= */
$sumBase=0; $sumTdsGst=0; $sumTdsIt=0; $sumTotal=0; $sumNet=0;
foreach($invoices as $iv){
    $sumBase   += (float)($iv['Amount'] ?? 0);
    $sumTdsGst += (float)($iv['TDSGSTAmount'] ?? 0);
    $sumTdsIt  += (float)($iv['TDSITAmount'] ?? 0);
    $sumTotal  += (float)($iv['TotalAmount'] ?? 0);
    $sumNet    += (float)($iv['NetPayable'] ?? 0);
}
?>

<style>
    @media print {

  /* hide buttons, modal close, sidebar, topbar etc */
  .no-print,
  .btn, 
  .modal-header,
  .modal-footer {
    display: none !important;
  }

  /* remove page background + padding */
  body {
    background: #fff !important;
  }

  /* make content full width */
  .page-content,
  .card,
  .inv-card,
  .bill-summary {
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
  }

  /* make sure each invoice prints nicely */
  .inv-card {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  /* table should not break weirdly */
  table {
    page-break-inside: auto;
  }
  tr, td, th {
    page-break-inside: avoid !important;
    break-inside: avoid-page !important;
  }

  /* colors may not print by default in browser */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}

.bill-summary{
  background:#f8fbff;border:1px solid #cfe2ff;border-radius:12px;padding:14px 16px;margin-bottom:14px;
}
.bill-k{font-size:12px;color:#6c757d;font-weight:700;text-transform:uppercase;}
.bill-v{font-size:15px;font-weight:700;}
.inv-card{border:1px solid #e9ecef;border-radius:14px;padding:14px 16px;margin-bottom:14px;background:#fff;}
.inv-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.inv-no{font-weight:800;color:#0d6efd;}
.small-muted{font-size:12px;color:#6c757d;}
.section-box{background:#fbfcff;border:1px solid #eef2ff;border-radius:12px;padding:12px 12px;margin-top:10px;}
.table-sm td,.table-sm th{vertical-align:middle;}
</style>

<!-- ======================= BILL SUMMARY ======================= -->
 <div class="d-flex justify-content-end mb-2 no-print">
  <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()">
    <i class="fa fa-print"></i> Print
  </button>
</div>

<div class="bill-summary">
    
  <div class="row g-2">
    <div class="col-md-3">
      <div class="bill-k">Bill Number</div>
      <div class="bill-v"><?= htmlspecialchars($bill['BillNumber'] ?? '-') ?></div>
    </div>
    <div class="col-md-3">
      <div class="bill-k">Received Date</div>
      <div class="bill-v"><?= !empty($bill['BillReceivedDate']) ? date('d-m-Y', strtotime($bill['BillReceivedDate'])) : '-' ?></div>
    </div>
    <div class="col-md-3">
      <div class="bill-k">From Section</div>
      <div class="bill-v"><?= htmlspecialchars($bill['ReceivedFromSection'] ?? '-') ?></div>
    </div>
    <div class="col-md-3">
      <div class="bill-k">Alloted To</div>
      <div class="bill-v"><?= htmlspecialchars($bill['AllotedTo'] ?? '-') ?></div>
    </div>

    <div class="col-md-3 mt-2">
      <div class="bill-k">Status</div>
      <div class="bill-v">
        <span class="badge <?= (($bill['Status'] ?? '')==='Pass'?'bg-success':'bg-secondary') ?>">
          <?= htmlspecialchars($bill['Status'] ?? 'DRAFT') ?>
        </span>
      </div>
    </div>

    <div class="col-md-9 mt-2">
      <div class="row g-2">
        <div class="col-md-3">
          <div class="bill-k">Invoices</div>
          <div class="bill-v"><?= count($invoices) ?></div>
        </div>
        <div class="col-md-3">
          <div class="bill-k">Base Total</div>
          <div class="bill-v"><?= nf($sumBase) ?></div>
        </div>
        <div class="col-md-3">
          <div class="bill-k">TDS Total</div>
          <div class="bill-v text-danger"><?= nf($sumTdsGst + $sumTdsIt) ?></div>
        </div>
        <div class="col-md-3">
          <div class="bill-k">Net Total</div>
          <div class="bill-v text-success"><?= nf($sumNet) ?></div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
foreach($invoices as $inv):

  $invoiceId = (int)$inv['Id'];
  $poId = (int)($inv['POId'] ?? 0);

  /* -------- PO MASTER -------- */
  $po = [];
  if($poId > 0){
    $poStmt = $conn->prepare("SELECT * FROM po_master WHERE Id = ?");
    $poStmt->execute([$poId]);
    $po = $poStmt->fetch(PDO::FETCH_ASSOC) ?: [];
  }

  /* -------- PO ITEMS -------- */
  $poItems = [];
  if($poId > 0){
    $poItemStmt = $conn->prepare("
      SELECT Id, ItemName,
             ISNULL(ItemAmount,0) AS ItemAmount,
             ISNULL(GSTPercent,0) AS GSTPercent,
             ISNULL(GSTAmount,0) AS GSTAmount,
             ISNULL(ITPercent,0) AS ITPercent,
             ISNULL(ITAmount,0) AS ITAmount,
             ISNULL(NetAmount,0) AS NetAmount
      FROM po_items WHERE POId=? ORDER BY Id ASC
    ");
    $poItemStmt->execute([$poId]);
    $poItems = $poItemStmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /* -------- PO BANK -------- */
  $poBank = [];
  if($poId > 0){
    $bkStmt = $conn->prepare("SELECT TOP 1 * FROM po_bank_details WHERE po_id=? AND is_active=1 ORDER BY id DESC");
    $bkStmt->execute([$poId]);
    $poBank = $bkStmt->fetch(PDO::FETCH_ASSOC) ?: [];
  }

  /* -------- SANCTIONS FOR THIS INVOICE -------- */
  $sanStmt = $conn->prepare("
    SELECT 
      so.Id,
      so.SanctionOrderNo,
      CONVERT(varchar(10), so.SanctionDate, 23) AS SanctionDate,
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

  /* -------- totals used all invoices for each sanction -------- */
  $sanTotals = [];
  foreach($sanctions as $s){
    $sid = (int)$s['Id'];
    $tStmt = $conn->prepare("SELECT SUM(ISNULL(SanctionBaseAmount,0)) AS TotalUsedBase FROM invoice_sanction_map WHERE SanctionId=?");
    $tStmt->execute([$sid]);
    $sanTotals[$sid] = (float)($tStmt->fetchColumn() ?? 0);
  }
?>

<div class="inv-card">
  <div class="inv-head">
    <div>
      <div class="inv-no">Invoice #<?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></div>
      <div class="small-muted">
        Date: <?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?>
        &nbsp; | &nbsp;
        Vendor: <?= htmlspecialchars($inv['VendorName'] ?? '-') ?>
      </div>
    </div>
    <div>
      <span class="badge bg-info"><?= htmlspecialchars($inv['BillType'] ?? 'Bill') ?></span>
    </div>
  </div>

  <!-- Invoice Details -->
  <div class="section-box">
    <div class="row g-2">
      <div class="col-md-4"><b>Department:</b> <?= htmlspecialchars($inv['DeptName'] ?? '-') ?></div>
      <div class="col-md-8"><b>HOA:</b> <?= htmlspecialchars($inv['HOA_NAME'] ?? '-') ?></div>
      <div class="col-md-4"><b>Received From:</b> <?= htmlspecialchars($inv['ReceivedFromSection'] ?? '-') ?></div>
      <div class="col-md-4"><b>Section DA:</b> <?= htmlspecialchars($inv['SectionDAName'] ?? '-') ?></div>
      <div class="col-md-4"><b>ECR Page No:</b> <?= htmlspecialchars($inv['EcrPageNo'] ?? '-') ?></div>
    </div>

    <hr class="my-2">

    <div class="row g-2">
      <div class="col-md-3"><b>Amount:</b> ₹ <?= nf($inv['Amount']) ?></div>
      <div class="col-md-3"><b>TDS GST (<?= nf($inv['TDSGSTPercent']) ?>%):</b> ₹ <span class="text-danger"><?= nf($inv['TDSGSTAmount']) ?></span></div>
      <div class="col-md-3"><b>TDS IT (<?= nf($inv['TDSITPercent']) ?>%):</b> ₹ <span class="text-danger"><?= nf($inv['TDSITAmount']) ?></span></div>
      <div class="col-md-3"><b>Net Payable:</b> ₹ <span class="text-success fw-bold"><?= nf($inv['NetPayable']) ?></span></div>

      <div class="col-md-3"><b>Total Amount:</b> ₹ <?= nf($inv['TotalAmount']) ?></div>
      <div class="col-md-3"><b>Credit To:</b> <?= htmlspecialchars($inv['CreditName'] ?? '-') ?></div>
      <div class="col-md-3"><b>Debit From:</b> <?= htmlspecialchars($inv['DebitName'] ?? '-') ?></div>
      <div class="col-md-3"><b>PFMS:</b> <?= htmlspecialchars($inv['PFMSNumber'] ?? '-') ?></div>
    </div>
  </div>

  <!-- Sanctions -->
  <div class="section-box">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-bold text-primary"><i class="fa fa-stamp"></i> Sanctions used for this Invoice</div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Sanction No</th>
            <th>Date</th>
            <th class="text-end">Sanction Amount</th>
            <th class="text-end">Used Base (This Invoice)</th>
            <th class="text-end">Total Used (All)</th>
            <th class="text-end">Balance</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($sanctions)): ?>
          <tr><td colspan="7" class="text-center text-muted">No sanction mapping found for this invoice</td></tr>
        <?php else: ?>
          <?php
            $k=1; $tSan=0; $tUsed=0; $tAll=0; $tBal=0;
            foreach($sanctions as $s):
              $sid=(int)$s['Id'];
              $sanAmt=(float)$s['SanctionAmount'];
              $used=(float)$s['UsedBase'];
              $all=(float)($sanTotals[$sid] ?? 0);
              $bal=$sanAmt - $all;

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
  </div>

  <!-- PO + Items -->
  <div class="section-box">
    <div class="fw-bold text-primary mb-2"><i class="fa fa-file-contract"></i> PO Details</div>
    <?php if(empty($po)): ?>
      <div class="text-muted">No PO linked for this invoice.</div>
    <?php else: ?>
      <div class="row g-2">
        <div class="col-md-3"><b>PO No:</b> <?= htmlspecialchars($po['POOrderNo'] ?? '-') ?></div>
        <div class="col-md-3"><b>PO Date:</b> <?= !empty($po['POOrderDate']) ? date('d-m-Y', strtotime($po['POOrderDate'])) : '-' ?></div>
        <div class="col-md-3"><b>GST No:</b> <?= htmlspecialchars($po['GSTNumber'] ?? '-') ?></div>
        <div class="col-md-3"><b>PO Base:</b> ₹ <?= nf($po['POAmount'] ?? 0) ?></div>
        <div class="col-md-3"><b>PO GST Amt:</b> ₹ <?= nf($po['POGSTAmount'] ?? 0) ?></div>
        <div class="col-md-3"><b>PO IT Amt:</b> ₹ <?= nf($po['POITAmount'] ?? 0) ?></div>
        <div class="col-md-3"><b>PO Net:</b> ₹ <span class="text-success fw-bold"><?= nf($po['PONetAmount'] ?? 0) ?></span></div>
      </div>

      <hr class="my-2">

      <div class="fw-bold mb-2">PO Items</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Item</th>
              <th class="text-end">Amount</th>
              <th class="text-end">GST%</th>
              <th class="text-end">GST</th>
              <th class="text-end">IT%</th>
              <th class="text-end">IT</th>
              <th class="text-end">Net</th>
            </tr>
          </thead>
          <tbody>
          <?php
          if(empty($poItems)){
            echo '<tr><td colspan="8" class="text-center text-muted">No PO items</td></tr>';
          } else {
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
          <?php } ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Bank -->
  <div class="section-box">
    <div class="fw-bold text-primary mb-2"><i class="fa fa-university"></i> Bank Details</div>
    <div class="row g-2">
      <div class="col-md-4"><b>PAN:</b> <?= htmlspecialchars($poBank['pan_number'] ?? ($inv['PanNumber'] ?? '-')) ?></div>
      <div class="col-md-4"><b>PFMS:</b> <?= htmlspecialchars($poBank['pfms_number'] ?? ($inv['PFMSNumber'] ?? '-')) ?></div>
      <div class="col-md-4"><b>Bank:</b> <?= htmlspecialchars($poBank['bank_name'] ?? ($inv['BankName'] ?? '-')) ?></div>
      <div class="col-md-4"><b>IFSC:</b> <?= htmlspecialchars($poBank['ifsc'] ?? ($inv['IFSC'] ?? '-')) ?></div>
      <div class="col-md-4"><b>Account:</b> <?= htmlspecialchars($poBank['account_number'] ?? ($inv['AccountNumber'] ?? '-')) ?></div>
    </div>
  </div>

</div>

<?php endforeach; ?>

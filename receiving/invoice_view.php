<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<div class="text-danger text-center">Invalid Invoice</div>';
    exit;
}

function nf($v) {
    return number_format((float)($v ?? 0), 2);
}

/* ========================= INVOICE MASTER ========================= */
$stmt = $conn->prepare("
    SELECT 
        im.*,
        fy.FinYear,
        d.DeptName,
        bt.BillType,
        cr.CreditName,
        dr.DebitName,
        h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName AS HOA_NAME,

        pm.POOrderNo,
        CONVERT(varchar(10), pm.POOrderDate, 23) AS POOrderDate,
        ISNULL(pm.POAmount,0)     AS POAmount,
        ISNULL(pm.PONetAmount,0)  AS PONetAmount,
        ISNULL(pm.POGSTAmount,0)  AS POGSTAmount,
        ISNULL(pm.POITAmount,0)   AS POITAmount,
        pm.GSTNumber

    FROM invoice_master im
    LEFT JOIN fin_year_master fy ON im.FinancialYearId = fy.Id
    LEFT JOIN dept_master d ON im.DeptId = d.Id
    LEFT JOIN bill_type_master bt ON im.BillTypeId = bt.Id
    LEFT JOIN account_credit_master cr ON im.CreditToId = cr.Id
    LEFT JOIN account_debit_master dr ON im.DebitFromId = dr.Id
    LEFT JOIN hoa_master h ON im.HOAId = h.HoaId
    LEFT JOIN po_master pm ON pm.Id = im.POId
    WHERE im.Id = ?
");
$stmt->execute([$id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) {
    echo '<div class="text-danger text-center">Invoice not found</div>';
    exit;
}

$poId = (int)($inv['POId'] ?? 0);

/* ========================= PO BANK DETAILS ========================= */
$poBank = [];
if ($poId > 0) {
    $bkStmt = $conn->prepare("SELECT TOP 1 * FROM po_bank_details WHERE po_id = ? AND is_active = 1 ORDER BY id DESC");
    $bkStmt->execute([$poId]);
    $poBank = $bkStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/* ========================= PO ITEMS ========================= */
$poItems = [];
$poItemSums = ['SumBase'=>0,'SumGST'=>0,'SumIT'=>0,'SumNet'=>0,'Cnt'=>0];

if ($poId > 0) {
    $itStmt = $conn->prepare("
        SELECT 
            Id, ItemName,
            ISNULL(ItemAmount,0) AS ItemAmount,
            ISNULL(GSTPercent,0) AS GSTPercent,
            ISNULL(GSTAmount,0)  AS GSTAmount,
            ISNULL(ITPercent,0)  AS ITPercent,
            ISNULL(ITAmount,0)   AS ITAmount,
            ISNULL(NetAmount,0)  AS NetAmount
        FROM po_items
        WHERE POId = ?
        ORDER BY Id ASC
    ");
    $itStmt->execute([$poId]);
    $poItems = $itStmt->fetchAll(PDO::FETCH_ASSOC);

    $sumStmt = $conn->prepare("
        SELECT 
            COUNT(*) AS Cnt,
            SUM(ISNULL(ItemAmount,0)) AS SumBase,
            SUM(ISNULL(GSTAmount,0))  AS SumGST,
            SUM(ISNULL(ITAmount,0))   AS SumIT,
            SUM(ISNULL(NetAmount,0))  AS SumNet
        FROM po_items
        WHERE POId = ?
    ");
    $sumStmt->execute([$poId]);
    $poItemSums = $sumStmt->fetch(PDO::FETCH_ASSOC) ?: $poItemSums;
}

/* ========================= SANCTIONS USED IN THIS INVOICE ========================= */
$sanctions = [];
$sanStmt = $conn->prepare("
    SELECT 
        so.Id,
        so.SanctionOrderNo,
        CONVERT(varchar(10), so.SanctionDate, 23) AS SanctionDate,
        ISNULL(so.SanctionAmount,0) AS SanctionAmount,
        ISNULL(so.SanctionNetAmount,0) AS SanctionNetAmount,
        ISNULL(so.GSTPercent,0) AS GSTPercent,
        ISNULL(so.ITPercent,0) AS ITPercent,

        -- used in THIS invoice (map)
        ISNULL(ism.SanctionBaseAmount,0) AS UsedBase,
        ISNULL(ism.GSTAmount,0)          AS UsedGST,
        ISNULL(ism.ITAmount,0)           AS UsedIT,
        ISNULL(ism.NetAmount,0)          AS UsedNet

    FROM invoice_sanction_map ism
    INNER JOIN sanction_order_master so ON so.Id = ism.SanctionId
    WHERE ism.InvoiceId = ?
    ORDER BY so.Id ASC
");
$sanStmt->execute([$id]);
$sanctions = $sanStmt->fetchAll(PDO::FETCH_ASSOC);

/* ========================= TOTAL USED (ALL INVOICES) FOR EACH SANCTION ========================= */
$sanTotals = []; // [SanctionId => TotalUsedBase]
foreach ($sanctions as $s) {
    $sid = (int)$s['Id'];

    $tStmt = $conn->prepare("
        SELECT 
            SUM(ISNULL(SanctionBaseAmount,0)) AS TotalUsedBase,
            SUM(ISNULL(GSTAmount,0))          AS TotalUsedGST,
            SUM(ISNULL(ITAmount,0))           AS TotalUsedIT,
            SUM(ISNULL(NetAmount,0))          AS TotalUsedNet
        FROM invoice_sanction_map
        WHERE SanctionId = ?
    ");
    $tStmt->execute([$sid]);
    $row = $tStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $sanTotals[$sid] = [
        'TotalUsedBase' => (float)($row['TotalUsedBase'] ?? 0),
        'TotalUsedGST'  => (float)($row['TotalUsedGST'] ?? 0),
        'TotalUsedIT'   => (float)($row['TotalUsedIT'] ?? 0),
        'TotalUsedNet'  => (float)($row['TotalUsedNet'] ?? 0),
    ];
}

/* ========================= PO % DISPLAY (FROM MASTER TOTALS) ========================= */
$poBaseMaster = (float)($inv['POAmount'] ?? 0);
$poGSTMaster  = (float)($inv['POGSTAmount'] ?? 0);
$poITMaster   = (float)($inv['POITAmount'] ?? 0);

$poGSTPercent = ($poBaseMaster > 0) ? round(($poGSTMaster * 100) / $poBaseMaster, 2) : 0;
$poITPercent  = ($poBaseMaster > 0) ? round(($poITMaster * 100) / $poBaseMaster, 2) : 0;
?>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-inv">Invoice</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-po">PO</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-items">PO Items</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-san">Sanctions</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank">Bank & Accounts</button></li>
</ul>

<div class="tab-content">

<!-- ================= TAB 1: INVOICE ================= -->
<div class="tab-pane fade show active" id="tab-inv">
  <div class="row g-2">
    <div class="col-md-4"><strong>Invoice No:</strong> <?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Invoice Date:</strong> <?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?></div>
    <div class="col-md-4"><strong>Vendor:</strong> <?= htmlspecialchars($inv['VendorName'] ?? '-') ?></div>

    <div class="col-md-4"><strong>Financial Year:</strong> <?= htmlspecialchars($inv['FinYear'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Department:</strong> <?= htmlspecialchars($inv['DeptName'] ?? '-') ?></div>
    <div class="col-md-4"><strong>HOA:</strong> <?= htmlspecialchars($inv['HOA_NAME'] ?? '-') ?></div>

    <div class="col-md-4"><strong>ECR Page No:</strong> <?= htmlspecialchars($inv['EcrPageNo'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Bill Type:</strong> <?= htmlspecialchars($inv['BillType'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Created Date:</strong> <?= htmlspecialchars($inv['CreatedDate'] ?? '-') ?></div>
  </div>

  <hr>

  <table class="table table-bordered table-sm">
    <tr><th>Invoice Amount</th><td class="text-end"><?= nf($inv['Amount']) ?></td></tr>
    <tr><th>TDS GST (<?= nf($inv['TDSGSTPercent']) ?>%)</th><td class="text-end text-danger"><?= nf($inv['TDSGSTAmount']) ?></td></tr>
    <tr><th>TDS IT (<?= nf($inv['TDSITPercent']) ?>%)</th><td class="text-end text-danger"><?= nf($inv['TDSITAmount']) ?></td></tr>
    <tr class="table-secondary fw-bold">
      <th>TDS Total</th>
      <td class="text-end"><?= nf(((float)($inv['TDSGSTAmount']??0))+((float)($inv['TDSITAmount']??0))) ?></td>
    </tr>
    <tr class="table-success fw-bold"><th>Net Payable</th><td class="text-end"><?= nf($inv['NetPayable']) ?></td></tr>
    <tr class="table-light fw-bold"><th>Total Amount</th><td class="text-end"><?= nf($inv['TotalAmount']) ?></td></tr>
  </table>
</div>

<!-- ================= TAB 2: PO ================= -->
<div class="tab-pane fade" id="tab-po">
  <div class="row g-2">
    <div class="col-md-4"><strong>PO No:</strong> <?= htmlspecialchars($inv['POOrderNo'] ?? '-') ?></div>
    <div class="col-md-4"><strong>PO Date:</strong> <?= !empty($inv['POOrderDate']) ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?></div>
    <div class="col-md-4"><strong>GST No:</strong> <?= htmlspecialchars($inv['GSTNumber'] ?? '-') ?></div>
  </div>

  <table class="table table-bordered table-sm mt-2">
    <tr><th>PO Base Amount</th><td class="text-end"><?= nf($inv['POAmount']) ?></td></tr>
    <tr><th>PO GST Amount (<?= $poGSTPercent ?>%)</th><td class="text-end"><?= nf($inv['POGSTAmount']) ?></td></tr>
    <tr><th>PO IT Amount (<?= $poITPercent ?>%)</th><td class="text-end"><?= nf($inv['POITAmount']) ?></td></tr>
    <tr class="table-secondary fw-bold"><th>PO Net Amount</th><td class="text-end"><?= nf($inv['PONetAmount']) ?></td></tr>
  </table>

  <div class="small text-muted">
    PO GST/IT % shown here is calculated from PO base & GST/IT total columns in <b>po_master</b>.
  </div>
</div>

<!-- ================= TAB 3: PO ITEMS ================= -->
<div class="tab-pane fade" id="tab-items">
  <div class="table-responsive">
    <table class="table table-bordered table-sm">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Item Name</th>
          <th class="text-end">Amount</th>
          <th class="text-end">GST %</th>
          <th class="text-end">GST</th>
          <th class="text-end">IT %</th>
          <th class="text-end">IT</th>
          <th class="text-end">Net</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($poItems)): ?>
        <tr><td colspan="8" class="text-center text-muted">No PO items found</td></tr>
      <?php else: ?>
        <?php $k=1; foreach($poItems as $it): ?>
          <tr>
            <td><?= $k++ ?></td>
            <td><?= htmlspecialchars($it['ItemName'] ?? '') ?></td>
            <td class="text-end"><?= nf($it['ItemAmount']) ?></td>
            <td class="text-end"><?= nf($it['GSTPercent']) ?></td>
            <td class="text-end"><?= nf($it['GSTAmount']) ?></td>
            <td class="text-end"><?= nf($it['ITPercent']) ?></td>
            <td class="text-end"><?= nf($it['ITAmount']) ?></td>
            <td class="text-end fw-bold"><?= nf($it['NetAmount']) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr class="table-secondary fw-bold">
          <td colspan="2" class="text-end">TOTAL</td>
          <td class="text-end"><?= nf($poItemSums['SumBase']) ?></td>
          <td></td>
          <td class="text-end"><?= nf($poItemSums['SumGST']) ?></td>
          <td></td>
          <td class="text-end"><?= nf($poItemSums['SumIT']) ?></td>
          <td class="text-end"><?= nf($poItemSums['SumNet']) ?></td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ================= TAB 4: SANCTIONS ================= -->
<div class="tab-pane fade" id="tab-san">
  <div class="table-responsive">
    <table class="table table-bordered table-sm">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Sanction No</th>
          <th>Date</th>

          <th class="text-end">Sanction Amount</th>
          <th class="text-end">Sanction Net</th>

          <th class="text-end">Used Base (This Invoice)</th>
          <th class="text-end">Used GST</th>
          <th class="text-end">Used IT</th>
          <th class="text-end">Used Net</th>

          <th class="text-end">Total Used (All Invoices)</th>
          <th class="text-end">Balance</th>
        </tr>
      </thead>
      <tbody>
      <?php
      if(empty($sanctions)){
          echo '<tr><td colspan="11" class="text-center text-muted">No sanctions linked for this invoice</td></tr>';
      } else {
          $x=1;
          $tSan=0; $tSanNet=0;
          $tUsedBase=0; $tUsedGST=0; $tUsedIT=0; $tUsedNet=0;
          $tAllUsed=0; $tBal=0;

          foreach($sanctions as $s){
              $sid = (int)$s['Id'];

              $sanAmt = (float)$s['SanctionAmount'];
              $sanNet = (float)$s['SanctionNetAmount'];

              $usedBase = (float)($s['UsedBase'] ?? 0);
              $usedGST  = (float)($s['UsedGST'] ?? 0);
              $usedIT   = (float)($s['UsedIT'] ?? 0);
              $usedNet  = (float)($s['UsedNet'] ?? 0);

              $allUsedBase = (float)($sanTotals[$sid]['TotalUsedBase'] ?? 0);
              $bal = $sanAmt - $allUsedBase;

              $tSan += $sanAmt;
              $tSanNet += $sanNet;

              $tUsedBase += $usedBase;
              $tUsedGST  += $usedGST;
              $tUsedIT   += $usedIT;
              $tUsedNet  += $usedNet;

              $tAllUsed  += $allUsedBase;
              $tBal      += $bal;
      ?>
        <tr>
          <td><?= $x++ ?></td>
          <td class="fw-bold"><?= htmlspecialchars($s['SanctionOrderNo'] ?? '') ?></td>
          <td><?= !empty($s['SanctionDate']) ? date('d-m-Y', strtotime($s['SanctionDate'])) : '-' ?></td>

          <td class="text-end"><?= nf($sanAmt) ?></td>
          <td class="text-end"><?= nf($sanNet) ?></td>

          <td class="text-end text-danger"><?= nf($usedBase) ?></td>
          <td class="text-end text-danger"><?= nf($usedGST) ?></td>
          <td class="text-end text-danger"><?= nf($usedIT) ?></td>
          <td class="text-end text-danger fw-bold"><?= nf($usedNet) ?></td>

          <td class="text-end"><?= nf($allUsedBase) ?></td>

          <td class="text-end fw-bold <?= ($bal < 0 ? 'text-danger' : 'text-success') ?>">
            <?= nf($bal) ?>
          </td>
        </tr>
      <?php } ?>
        <tr class="table-secondary fw-bold">
          <td colspan="3" class="text-end">TOTAL</td>
          <td class="text-end"><?= nf($tSan) ?></td>
          <td class="text-end"><?= nf($tSanNet) ?></td>

          <td class="text-end text-danger"><?= nf($tUsedBase) ?></td>
          <td class="text-end text-danger"><?= nf($tUsedGST) ?></td>
          <td class="text-end text-danger"><?= nf($tUsedIT) ?></td>
          <td class="text-end text-danger"><?= nf($tUsedNet) ?></td>

          <td class="text-end"><?= nf($tAllUsed) ?></td>
          <td class="text-end"><?= nf($tBal) ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ================= TAB 5: BANK & ACCOUNTS ================= -->
<div class="tab-pane fade" id="tab-bank">
  <div class="row g-2">
    <div class="col-md-4"><strong>PAN:</strong> <?= htmlspecialchars($poBank['pan_number'] ?? ($inv['PanNumber'] ?? '-')) ?></div>
    <div class="col-md-4"><strong>PFMS:</strong> <?= htmlspecialchars($poBank['pfms_number'] ?? ($inv['PFMSNumber'] ?? '-')) ?></div>
    <div class="col-md-4"><strong>Bank:</strong> <?= htmlspecialchars($poBank['bank_name'] ?? ($inv['BankName'] ?? '-')) ?></div>

    <div class="col-md-4"><strong>IFSC:</strong> <?= htmlspecialchars($poBank['ifsc'] ?? ($inv['IFSC'] ?? '-')) ?></div>
    <div class="col-md-4"><strong>Account:</strong> <?= htmlspecialchars($poBank['account_number'] ?? ($inv['AccountNumber'] ?? '-')) ?></div>

    <div class="col-md-4"><strong>Received From:</strong> <?= htmlspecialchars($inv['ReceivedFromSection'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Section DA:</strong> <?= htmlspecialchars($inv['SectionDAName'] ?? '-') ?></div>

    <div class="col-md-4"><strong>Credit To:</strong> <?= htmlspecialchars($inv['CreditName'] ?? '-') ?></div>
    <div class="col-md-4"><strong>Debit From:</strong> <?= htmlspecialchars($inv['DebitName'] ?? '-') ?></div>
  </div>
</div>

</div>

<script>
$('#fullPageView').attr('href', 'invoice_full_view.php?id=<?= (int)$inv['Id'] ?>');
</script>

<?php
include '../config/db.php';
include '../includes/auth.php';

$billId = intval($_GET['id'] ?? 0);
if ($billId <= 0) die("Invalid Bill ID");

function nf($v){ return number_format((float)($v ?? 0), 2); }

/* =========================
   1) BILL MASTER + LATEST ENTRY + INVOICE AGG
   ========================= */
$billStmt = $conn->prepare("
    WITH BillLatest AS (
        SELECT
            be.BillInitialId,
            be.Id AS BillEntryId,
            be.Status AS CurrentStatus,
            be.TokenNo,
            be.AllotedDealingAsst,
            be.AllotedDate,
            be.Remarks AS BillEntryRemarks,
            be.DebitFromId,
            be.CreditToId,
            ROW_NUMBER() OVER (PARTITION BY be.BillInitialId ORDER BY be.Id DESC) AS rn
        FROM bill_entry be
    ),
    InvoiceAgg AS (
        SELECT
            bim.BillInitialId,
            COUNT(DISTINCT im.Id) AS InvoiceCount,
            MAX(im.ReceivedFromSection) AS ReceivedFromSection,
            MAX(im.BillTypeId) AS BillTypeId,

            SUM(ISNULL(im.Amount,0))        AS SumBaseAmount,
            SUM(ISNULL(im.TDSGSTAmount,0))  AS SumTDSGSTAmount,
            SUM(ISNULL(im.TDSITAmount,0))   AS SumTDSITAmount,
            SUM(ISNULL(im.TDS,0))           AS SumTDS,
            SUM(ISNULL(im.TotalAmount,0))   AS SumTotalAmount,
            SUM(ISNULL(im.NetPayable,0))    AS SumNetPayable
        FROM bill_invoice_map bim
        JOIN invoice_master im ON im.Id = bim.InvoiceId
        WHERE bim.BillInitialId = ?
        GROUP BY bim.BillInitialId
    )
    SELECT
        bi.Id,
        bi.BillNumber,
        bi.BillReceivedDate,
        bi.CreatedDate,
        bi.Status AS InitialStatus,
        bi.TransNumber,

        ia.InvoiceCount,
        ia.ReceivedFromSection,
        btm.BillType,

        bl.CurrentStatus,
        bl.TokenNo,
        bl.AllotedDate,
        bl.BillEntryRemarks,
        emp.EmployeeName AS AllotedName,

        ac.CreditName AS CreditTo,
        ad.DebitName  AS DebitFrom,

        ia.SumBaseAmount,
        ia.SumTDSGSTAmount,
        ia.SumTDSITAmount,
        ia.SumTDS,
        ia.SumTotalAmount,
        ia.SumNetPayable

    FROM bill_initial_entry bi
    LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = bi.Id
    LEFT JOIN bill_type_master btm ON btm.Id = ia.BillTypeId

    LEFT JOIN BillLatest bl ON bl.BillInitialId = bi.Id AND bl.rn = 1
    LEFT JOIN employee_master emp ON emp.Id = bl.AllotedDealingAsst

    LEFT JOIN account_credit_master ac ON ac.Id = bl.CreditToId
    LEFT JOIN account_debit_master  ad ON ad.Id = bl.DebitFromId

    WHERE bi.Id = ?
");
$billStmt->execute([$billId, $billId]);
$bill = $billStmt->fetch(PDO::FETCH_ASSOC);
if (!$bill) die("Bill not found");

/* =========================
   2) PROCESS HISTORY
   ========================= */
$processStmt = $conn->prepare("
    SELECT Status, ReasonForReturn, Remarks, ProcessedDate
    FROM bill_process
    WHERE BillId = ?
    ORDER BY ProcessedDate ASC
");
$processStmt->execute([$billId]);
$processHistory = $processStmt->fetchAll(PDO::FETCH_ASSOC);

$returnedCount = 0;
$passedCount = 0;
foreach ($processHistory as $p) {
    if (($p['Status'] ?? '') === 'Return') $returnedCount++;
    if (($p['Status'] ?? '') === 'Pass')   $passedCount++;
}

/* =========================
   3) TRANSACTION + FINAL ACCOUNTS
   ========================= */
$txnStmt = $conn->prepare("SELECT * FROM bill_transactions WHERE BillId = ?");
$txnStmt->execute([$billId]);
$transaction = $txnStmt->fetch(PDO::FETCH_ASSOC);

$finalStmt = $conn->prepare("SELECT * FROM final_accounts WHERE BillId = ?");
$finalStmt->execute([$billId]);
$finalAccount = $finalStmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   4) INVOICES (FIXED: NO GSTAmount/ITAmount, NO SanctionId)
   ========================= */
$invoiceStmt = $conn->prepare("
SELECT
    i.Id,
    i.InvoiceNo,
    i.InvoiceDate,
    i.VendorName,
    i.ReceivedFromSection,
    d.DeptName,
    bt.BillType,

    ISNULL(i.Amount,0)        AS Amount,
    ISNULL(i.TotalAmount,0)   AS TotalAmount,
    ISNULL(i.TDSGSTPercent,0) AS TDSGSTPercent,
    ISNULL(i.TDSGSTAmount,0)  AS TDSGSTAmount,
    ISNULL(i.TDSITPercent,0)  AS TDSITPercent,
    ISNULL(i.TDSITAmount,0)   AS TDSITAmount,
    ISNULL(i.TDS,0)           AS TDS,
    ISNULL(i.NetPayable,0)    AS NetPayable,

    i.BankName, i.AccountNumber, i.IFSC, i.PanNumber, i.PFMSNumber,

    (h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName) AS HOA_NAME,

    cr.CreditName,
    dr.DebitName,

    pm.Id AS POId,
    pm.POOrderNo,
    pm.POOrderDate,
    pm.GSTNumber,
    ISNULL(pm.POAmount,0)     AS POAmount,
    ISNULL(pm.POGSTAmount,0)  AS POGSTAmount,
    ISNULL(pm.POITAmount,0)   AS POITAmount,
    ISNULL(pm.PONetAmount,0)  AS PONetAmount,

    ISNULL(pis.ItemCount,0)     AS POItemCount,
    ISNULL(pis.SumItemAmount,0) AS POSumItemAmount,
    ISNULL(pis.SumItemGST,0)    AS POSumItemGST,
    ISNULL(pis.SumItemIT,0)     AS POSumItemIT,
    ISNULL(pis.SumItemNet,0)    AS POSumItemNet,

    pb.pan_number     AS POBankPAN,
    pb.pfms_number    AS POBankPFMS,
    pb.bank_name      AS POBankName,
    pb.ifsc           AS POBankIFSC,
    pb.account_number AS POBankAccount,

    st.SanctionNos,
    ISNULL(st.SumSanBase,0) AS SanctionUsedBase,
    ISNULL(st.SumSanGST,0)  AS SanctionUsedGST,
    ISNULL(st.SumSanIT,0)   AS SanctionUsedIT,
    ISNULL(st.SumSanNet,0)  AS SanctionUsedNet,
    st.SanctionDetails

FROM bill_invoice_map bim
JOIN invoice_master i ON i.Id = bim.InvoiceId

LEFT JOIN dept_master d ON d.Id = i.DeptId
LEFT JOIN bill_type_master bt ON bt.Id = i.BillTypeId
LEFT JOIN hoa_master h ON h.HoaId = i.HOAId
LEFT JOIN account_credit_master cr ON cr.Id = i.CreditToId
LEFT JOIN account_debit_master dr ON dr.Id = i.DebitFromId

LEFT JOIN po_master pm ON pm.Id = i.POId

OUTER APPLY (
    SELECT TOP 1 *
    FROM po_bank_details
    WHERE po_id = pm.Id AND is_active = 1
    ORDER BY id DESC
) pb

OUTER APPLY (
    SELECT
        COUNT(*) AS ItemCount,
        SUM(ISNULL(pi.ItemAmount,0)) AS SumItemAmount,
        SUM(ISNULL(pi.GSTAmount,0))  AS SumItemGST,
        SUM(ISNULL(pi.ITAmount,0))   AS SumItemIT,
        SUM(ISNULL(pi.NetAmount,0))  AS SumItemNet
    FROM po_items pi
    WHERE pi.POId = pm.Id
) pis

OUTER APPLY (
    SELECT
        STRING_AGG(x.SanctionOrderNo, ', ') AS SanctionNos,
        SUM(x.UsedBase) AS SumSanBase,
        SUM(x.UsedGST)  AS SumSanGST,
        SUM(x.UsedIT)   AS SumSanIT,
        SUM(x.UsedNet)  AS SumSanNet,
        STRING_AGG(
            CONCAT(
                x.SanctionOrderNo, ' [',
                CONVERT(varchar(10), x.SanctionDate, 105),
                '] Base:', FORMAT(x.UsedBase,'N2'),
                ' GST:', FORMAT(x.UsedGST,'N2'),
                ' IT:',  FORMAT(x.UsedIT,'N2'),
                ' Net:', FORMAT(x.UsedNet,'N2')
            ),
            ' | '
        ) AS SanctionDetails
    FROM (
        SELECT DISTINCT
            so2.SanctionOrderNo,
            so2.SanctionDate,
            ISNULL(ism.SanctionBaseAmount,0) AS UsedBase,
            ISNULL(ism.GSTAmount,0)          AS UsedGST,
            ISNULL(ism.ITAmount,0)           AS UsedIT,
            ISNULL(ism.NetAmount,0)          AS UsedNet
        FROM invoice_sanction_map ism
        JOIN sanction_order_master so2 ON so2.Id = ism.SanctionId
        WHERE ism.InvoiceId = i.Id
    ) x
) st

WHERE bim.BillInitialId = ?
ORDER BY i.InvoiceDate DESC, i.Id DESC
");
$invoiceStmt->execute([$billId]);
$invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bill History</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<style>
@media print {
    body { background: #fff !important; }
    .btn, .no-print, .navbar, .sidebar { display: none !important; }
    .card { border:1px solid #000 !important; box-shadow:none !important; margin-bottom:15px !important; }
    .card-header { background:#f0f0f0 !important; color:#000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    table { width:100% !important; border-collapse:collapse !important; }
    table th, table td { border:1px solid #000 !important; padding:6px !important; font-size:12px; }
    h4,h5 { page-break-after:avoid; }
    tr { page-break-inside:avoid; }
    
}
.inv-details-row{display:none;}
.inv-details-row td{ background:#fff; }
.inv-details-box{
    background:#fbfcff;
    border:1px solid #e9ecef;
    border-radius:12px;
    padding:14px;
}
.small-h{font-size:12px;color:#6c757d;font-weight:700;text-transform:uppercase;}
</style>

<body class="bg-light">
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-3">

    <div class="d-flex justify-content-end mb-3 no-print">
        <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print Bill History</button>
    </div>

    <!-- BILL INFO -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            📄 Bill History – <?= htmlspecialchars($bill['BillNumber'] ?? '-') ?>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3"><strong>Received Date:</strong> <?= !empty($bill['BillReceivedDate']) ? date('d-m-Y', strtotime($bill['BillReceivedDate'])) : '-' ?></div>
                <div class="col-md-3"><strong>From Section:</strong> <?= htmlspecialchars($bill['ReceivedFromSection'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Bill Type:</strong> <?= htmlspecialchars($bill['BillType'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-info"><?= htmlspecialchars($bill['CurrentStatus'] ?? 'Draft') ?></span></div>

                <div class="col-md-3"><strong>Token No:</strong> <?= htmlspecialchars($bill['TokenNo'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Alloted To:</strong> <?= htmlspecialchars($bill['AllotedName'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Alloted Date:</strong> <?= !empty($bill['AllotedDate']) ? date('d-m-Y', strtotime($bill['AllotedDate'])) : '-' ?></div>
                <div class="col-md-3"><strong>Invoices:</strong> <?= (int)($bill['InvoiceCount'] ?? 0) ?></div>

                <div class="col-md-6"><strong>Debit From:</strong> <?= htmlspecialchars($bill['DebitFrom'] ?? '-') ?></div>
                <div class="col-md-6"><strong>Credit To:</strong> <?= htmlspecialchars($bill['CreditTo'] ?? '-') ?></div>
            </div>

            <hr>

            <div class="row g-2">
                <div class="col-md-3"><strong>Base Total:</strong> ₹ <?= nf($bill['SumBaseAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>TDS GST Total:</strong> ₹ <?= nf($bill['SumTDSGSTAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>TDS IT Total:</strong> ₹ <?= nf($bill['SumTDSITAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Total TDS:</strong> ₹ <?= nf($bill['SumTDS'] ?? 0) ?></div>

                <div class="col-md-3"><strong>Gross Total:</strong> ₹ <?= nf($bill['SumTotalAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Net Payable:</strong> ₹ <?= nf($bill['SumNetPayable'] ?? 0) ?></div>
                <div class="col-md-3">🔄 Returned: <strong><?= (int)$returnedCount ?></strong></div>
                <div class="col-md-3">✔ Passed: <strong><?= (int)$passedCount ?></strong></div>
            </div>

            <?php if(!empty($bill['BillEntryRemarks'])): ?>
                <hr>
                <div><strong>Last Remarks:</strong> <?= nl2br(htmlspecialchars($bill['BillEntryRemarks'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PROCESS HISTORY -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning fw-bold">🔁 Process History</div>
        <div class="card-body">
            <?php if (count($processHistory) === 0): ?>
                <p class="text-muted">No processing history available.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-secondary">
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processHistory as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if (($p['Status'] ?? '') === 'Return'): ?>
                                    <span class="badge bg-danger">Returned</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Passed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($p['ProcessedDate']) ? date('d-m-Y H:i', strtotime($p['ProcessedDate'])) : '-' ?></td>
                            <td><?= htmlspecialchars($p['ReasonForReturn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['Remarks'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- INVOICES -->
    <<div class="card shadow-sm mb-4">
  <div class="card-header bg-info text-white fw-bold">📎 Attached Invoices + PO + Sanction (Detailed)</div>
  <div class="card-body">

  <?php if(empty($invoices)): ?>
      <p class="text-muted">No invoices attached to this bill.</p>
  <?php else: ?>

    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm align-middle">
        <thead class="table-secondary">
          <tr class="text-center">
            <th>#</th>
            <th>Invoice</th>
            <th>Vendor / Dept</th>
            <th class="text-end">Total</th>
            <th class="text-end">TDS (GST+IT)</th>
            <th class="text-end">Net</th>
            <th>PO No</th>
            <th>Sanctions</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach($invoices as $k => $inv): 
            $tdsTotal = (float)($inv['TDSGSTAmount'] ?? 0) + (float)($inv['TDSITAmount'] ?? 0);
            $rowId = (int)$inv['Id'];
        ?>
          <tr>
            <td class="text-center"><?= $k+1 ?></td>

            <td>
              <div class="fw-bold"><?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></div>
              <div class="small text-muted">
                <?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?>
                • <span class="badge bg-light text-dark"><?= htmlspecialchars($inv['BillType'] ?? '-') ?></span>
              </div>
            </td>

            <td>
              <div class="fw-bold"><?= htmlspecialchars($inv['VendorName'] ?? '-') ?></div>
              <div class="small text-muted"><?= htmlspecialchars($inv['DeptName'] ?? '-') ?></div>
              <div class="small">From: <?= htmlspecialchars($inv['ReceivedFromSection'] ?? '-') ?></div>
            </td>

            <td class="text-end">₹ <?= number_format((float)($inv['TotalAmount'] ?? 0),2) ?></td>
            <td class="text-end text-danger">₹ <?= number_format($tdsTotal,2) ?></td>
            <td class="text-end fw-bold text-success">₹ <?= number_format((float)($inv['NetPayable'] ?? 0),2) ?></td>

            <td>
              <div class="fw-bold"><?= htmlspecialchars($inv['POOrderNo'] ?? '-') ?></div>
              <div class="small text-muted">
                <?= !empty($inv['POOrderDate']) ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?>
              </div>
            </td>

            <td style="min-width:220px;">
              <div class="small"><b>Mapped:</b> <?= htmlspecialchars($inv['SanctionNos'] ?? '-') ?></div>
              <div class="small text-muted">
                Used Net: ₹ <?= number_format((float)($inv['SanctionUsedNet'] ?? 0),2) ?>
              </div>
            </td>

            <td class="text-center">
             <button type="button"
        class="btn btn-sm btn-outline-primary btn-toggle"
        data-id="<?= (int)$inv['Id'] ?>">
  View
</button>
            </td>
          </tr>

          <!-- EXPAND ROW -->
         <tr class="inv-details-row" id="detRow<?= (int)$inv['Id'] ?>" style="display:none;">
  <td colspan="9">
    <div class="inv-details-box" style="display:none;">

                <div class="row g-3">
                  <!-- HOA + Credit/Debit -->
                  <div class="col-md-6">
                    <div class="small-h">HOA</div>
                    <div><?= htmlspecialchars($inv['HOA_NAME'] ?? '-') ?></div>
                    <div class="small text-muted mt-1">
                      Credit: <?= htmlspecialchars($inv['CreditName'] ?? '-') ?> |
                      Debit: <?= htmlspecialchars($inv['DebitName'] ?? '-') ?>
                    </div>
                  </div>

                  <!-- Invoice Bank -->
                  <div class="col-md-6">
                    <div class="small-h">Invoice Bank</div>
                    <div><b><?= htmlspecialchars($inv['BankName'] ?? '-') ?></b></div>
                    <div class="small">A/C: <?= htmlspecialchars($inv['AccountNumber'] ?? '-') ?></div>
                    <div class="small">IFSC: <?= htmlspecialchars($inv['IFSC'] ?? '-') ?></div>
                    <div class="small">PAN: <?= htmlspecialchars($inv['PanNumber'] ?? '-') ?> | PFMS: <?= htmlspecialchars($inv['PFMSNumber'] ?? '-') ?></div>
                  </div>

                  <!-- PO Summary -->
                  <div class="col-md-6">
                    <div class="small-h">PO Summary</div>
                    <div class="row">
                      <div class="col-6 small">PO Base: ₹ <?= number_format((float)($inv['POAmount'] ?? 0),2) ?></div>
                      <div class="col-6 small">PO GST: ₹ <?= number_format((float)($inv['POGSTAmount'] ?? 0),2) ?></div>
                      <div class="col-6 small">PO IT: ₹ <?= number_format((float)($inv['POITAmount'] ?? 0),2) ?></div>
                      <div class="col-6 small fw-bold text-success">PO Net: ₹ <?= number_format((float)($inv['PONetAmount'] ?? 0),2) ?></div>
                    </div>

                    <div class="mt-2 small text-muted">
                      Items: <?= (int)($inv['POItemCount'] ?? 0) ?> |
                      Item Base: ₹ <?= number_format((float)($inv['POSumItemAmount'] ?? 0),2) ?> |
                      Item GST: ₹ <?= number_format((float)($inv['POSumItemGST'] ?? 0),2) ?> |
                      Item IT: ₹ <?= number_format((float)($inv['POSumItemIT'] ?? 0),2) ?> |
                      Item Net: ₹ <?= number_format((float)($inv['POSumItemNet'] ?? 0),2) ?>
                    </div>
                  </div>

                  <!-- PO Bank -->
                  <div class="col-md-6">
                    <div class="small-h">PO Bank (Active)</div>
                    <div><b><?= htmlspecialchars($inv['POBankName'] ?? '-') ?></b></div>
                    <div class="small">A/C: <?= htmlspecialchars($inv['POBankAccount'] ?? '-') ?></div>
                    <div class="small">IFSC: <?= htmlspecialchars($inv['POBankIFSC'] ?? '-') ?></div>
                    <div class="small">PAN: <?= htmlspecialchars($inv['POBankPAN'] ?? '-') ?> | PFMS: <?= htmlspecialchars($inv['POBankPFMS'] ?? '-') ?></div>
                    <div class="small">GST No: <?= htmlspecialchars($inv['GSTNumber'] ?? '-') ?></div>
                  </div>

                  <!-- Sanction Details -->
                  <div class="col-12">
                    <div class="small-h">Sanction Details (Invoice Mapping)</div>
                    <div class="small">
                      <b>Used Base:</b> ₹ <?= number_format((float)($inv['SanctionUsedBase'] ?? 0),2) ?> |
                      <b>Used GST:</b> ₹ <?= number_format((float)($inv['SanctionUsedGST'] ?? 0),2) ?> |
                      <b>Used IT:</b> ₹ <?= number_format((float)($inv['SanctionUsedIT'] ?? 0),2) ?> |
                      <b>Used Net:</b> ₹ <?= number_format((float)($inv['SanctionUsedNet'] ?? 0),2) ?>
                    </div>

                    <div class="mt-2 p-2 bg-light rounded small" style="white-space:normal;">
                      <?= htmlspecialchars($inv['SanctionDetails'] ?? '-') ?>
                    </div>
                  </div>
                </div>

              </div>
            </td>
          </tr>

        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php endif; ?>
  </div>
</div>
    <!-- TRANSACTION -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white fw-bold">💳 Transaction</div>
        <div class="card-body">
            <?php if ($transaction): ?>
                <p><strong>Created Date:</strong> <?= !empty($transaction['CreatedDate']) ? date('d-m-Y H:i', strtotime($transaction['CreatedDate'])) : '-' ?></p>
                <p><strong>Batch No:</strong> <?= htmlspecialchars($transaction['BatchNo'] ?? '-') ?></p>
                <p><strong>Voucher No:</strong> <?= htmlspecialchars($transaction['VoucherNo'] ?? '-') ?></p>
            <?php else: ?>
                <p class="text-muted">Transaction not initiated yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- FINAL ACCOUNTS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white fw-bold">🧾 Final Accounts</div>
        <div class="card-body">
            <?php if ($finalAccount): ?>
                <p><strong>Voucher No:</strong> <?= htmlspecialchars($finalAccount['VoucherNo'] ?? '-') ?></p>
                <p><strong>Voucher Date:</strong> <?= !empty($finalAccount['VoucherDate']) ? date('d-m-Y', strtotime($finalAccount['VoucherDate'])) : '-' ?></p>
                <p><strong>PFMS Advice No:</strong> <?= htmlspecialchars($finalAccount['PFMSAdviceNo'] ?? '-') ?></p>
                <p><strong>Remarks:</strong> <?= nl2br(htmlspecialchars($finalAccount['Remarks'] ?? '-')) ?></p>
            <?php else: ?>
                <p class="text-muted">Final account not generated yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- <div class="text-end no-print mb-4">
        <a href="bill_entry_list.php" class="btn btn-secondary">← Back to Bills</a>
    </div> -->

</div>
</body>
<script src="../js/jquery-3.7.1.min.js"></script>

<!-- Bootstrap -->
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>

<!-- DataTables (if used) -->
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>
<script>
$(document).on('click', '.btn-toggle', function () {
    const id  = $(this).data('id');
    const row = $('#detRow' + id);
    const box = row.find('.inv-details-box');

    // close other open rows (optional)
    $('.inv-details-row').not(row).each(function(){
        $(this).find('.inv-details-box').stop(true,true).slideUp(150);
        $(this).hide();
    });

    // toggle current
    if(row.is(':visible')){
        box.stop(true,true).slideUp(150, function(){ row.hide(); });
    } else {
        row.show();
        box.stop(true,true).slideDown(200);
        $('html, body').animate({ scrollTop: row.offset().top - 120 }, 200);
    }
});
</script>
</html>

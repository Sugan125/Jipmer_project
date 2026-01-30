<?php
include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);

/* ============ AUTH CHECK (same as yours) ============ */
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");


/* ============ BILL ID ============ */
$billId = intval($_GET['id'] ?? 0);
if ($billId <= 0) die("Invalid Bill ID");

function nf($v){ return number_format((float)($v ?? 0), 2); }

/* =========================================================
   1) BILL MASTER + LATEST BILL ENTRY (Status/Token/Allot)
   + InvoiceAgg (From Section + BillType)
   + Invoice totals (Base/GST/IT/TDS/Net) from invoice_master
   ========================================================= */
$billStmt = $conn->prepare("
    WITH BillLatest AS (
        SELECT
            be.BillInitialId,
            be.Id AS BillEntryId,
            be.Status AS CurrentStatus,
            be.TokenNo,
            be.AllotedDealingAsst,
            be.AllotDate,
            be.Remarks AS BillEntryRemarks,
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
            SUM(ISNULL(im.GSTAmount,0))     AS SumGSTAmount,
            SUM(ISNULL(im.ITAmount,0))      AS SumITAmount,
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

        ia.InvoiceCount,
        ia.ReceivedFromSection,
        btm.BillType,

        bl.CurrentStatus,
        bl.TokenNo,
        bl.AllotDate,
        bl.BillEntryRemarks,
        emp.EmployeeName AS AllotedName,

        ia.SumBaseAmount,
        ia.SumGSTAmount,
        ia.SumITAmount,
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
    WHERE bi.Id = ?
");
$billStmt->execute([$billId, $billId]);
$bill = $billStmt->fetch(PDO::FETCH_ASSOC);
if (!$bill) die("Bill not found");

/* =========================================================
   2) PROCESS HISTORY
   ========================================================= */
$processStmt = $conn->prepare("
    SELECT Status, Remarks, ProcessedDate
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

/* =========================================================
   3) TRANSACTION + FINAL ACCOUNTS (same as yours)
   ========================================================= */
$txnStmt = $conn->prepare("SELECT * FROM bill_transactions WHERE BillId = ?");
$txnStmt->execute([$billId]);
$transaction = $txnStmt->fetch(PDO::FETCH_ASSOC);

$finalStmt = $conn->prepare("SELECT * FROM final_accounts WHERE BillId = ?");
$finalStmt->execute([$billId]);
$finalAccount = $finalStmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   4) INVOICES (MORE DETAILED)
   - Dept, HOA, Credit/Debit names
   - Sanction details (direct sanction + mapped sanctions list)
   - PO details
   ========================================================= */
$invoiceStmt = $conn->prepare("
    SELECT
        i.Id,
        i.InvoiceNo,
        i.InvoiceDate,
        i.VendorName,
        i.ReceivedFromSection,
        d.DeptName,
        bt.BillType,

        -- Amounts
        ISNULL(i.Amount,0)        AS Amount,
        ISNULL(i.GSTAmount,0)     AS GSTAmount,
        ISNULL(i.ITAmount,0)      AS ITAmount,
        ISNULL(i.TotalAmount,0)   AS TotalAmount,
        ISNULL(i.TDSGSTPercent,0) AS TDSGSTPercent,
        ISNULL(i.TDSGSTAmount,0)  AS TDSGSTAmount,
        ISNULL(i.TDSITPercent,0)  AS TDSITPercent,
        ISNULL(i.TDSITAmount,0)   AS TDSITAmount,
        ISNULL(i.TDS,0)           AS TDS,
        ISNULL(i.NetPayable,0)    AS NetPayable,

        -- Bank
        i.BankName, i.AccountNumber, i.IFSC, i.PanNumber, i.PFMSNumber,

        -- HOA
        (h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName) AS HOA_NAME,

        -- Credit/Debit
        cr.CreditName,
        dr.DebitName,

        -- Direct sanction/PO (if stored in invoice_master)
        so.SanctionOrderNo AS DirectSanctionNo,
        so.SanctionDate    AS DirectSanctionDate,
        pm.POOrderNo,
        pm.POOrderDate,
        ISNULL(pm.POAmount,0)     AS POAmount,
        ISNULL(pm.POGSTAmount,0)  AS POGSTAmount,
        ISNULL(pm.POITAmount,0)   AS POITAmount,
        ISNULL(pm.PONetAmount,0)  AS PONetAmount,

        -- If multiple sanctions are mapped, show list
        st.SanctionNos

    FROM bill_invoice_map bim
    JOIN invoice_master i ON i.Id = bim.InvoiceId

    LEFT JOIN dept_master d ON d.Id = i.DeptId
    LEFT JOIN bill_type_master bt ON bt.Id = i.BillTypeId
    LEFT JOIN hoa_master h ON h.HOAId = i.HOAId
    LEFT JOIN account_credit_master cr ON cr.Id = i.CreditToId
    LEFT JOIN account_debit_master dr ON dr.Id = i.DebitFromId

    LEFT JOIN sanction_order_master so ON so.Id = i.SanctionId
    LEFT JOIN po_master pm ON pm.Id = i.POId

    OUTER APPLY (
        SELECT STRING_AGG(DISTINCT so2.SanctionOrderNo, ', ') AS SanctionNos
        FROM invoice_sanction_map ism
        JOIN sanction_order_master so2 ON so2.Id = ism.SanctionId
        WHERE ism.InvoiceId = i.Id
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
</style>

<body class="bg-light">
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-3">

    <div class="d-flex justify-content-end mb-3 no-print">
        <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print Bill History</button>
    </div>

    <!-- BILL INFO (MORE DETAILED) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            📄 Bill History – <?= htmlspecialchars($bill['BillNumber'] ?? '-') ?>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3"><strong>Received Date:</strong> <?= !empty($bill['BillReceivedDate']) ? date('d-m-Y', strtotime($bill['BillReceivedDate'])) : '-' ?></div>
                <div class="col-md-3"><strong>From Section:</strong> <?= htmlspecialchars($bill['ReceivedFromSection'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Bill Type:</strong> <?= htmlspecialchars($bill['BillType'] ?? '-') ?></div>
                <div class="col-md-3">
                    <strong>Current Status:</strong>
                    <span class="badge bg-info"><?= htmlspecialchars($bill['CurrentStatus'] ?? 'Draft') ?></span>
                </div>

                <div class="col-md-3"><strong>Token No:</strong> <?= htmlspecialchars($bill['TokenNo'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Alloted To:</strong> <?= htmlspecialchars($bill['AllotedName'] ?? '-') ?></div>
                <div class="col-md-3"><strong>Allot Date:</strong> <?= !empty($bill['AllotDate']) ? date('d-m-Y', strtotime($bill['AllotDate'])) : '-' ?></div>
                <div class="col-md-3"><strong>Invoices:</strong> <?= (int)($bill['InvoiceCount'] ?? 0) ?></div>
            </div>

            <hr>

            <div class="row g-2">
                <div class="col-md-3"><strong>Base Total:</strong> ₹ <?= nf($bill['SumBaseAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>GST Total:</strong> ₹ <?= nf($bill['SumGSTAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>IT Total:</strong> ₹ <?= nf($bill['SumITAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Gross Total:</strong> ₹ <?= nf($bill['SumTotalAmount'] ?? 0) ?></div>

                <div class="col-md-3"><strong>TDS GST Total:</strong> ₹ <?= nf($bill['SumTDSGSTAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>TDS IT Total:</strong> ₹ <?= nf($bill['SumTDSITAmount'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Total TDS:</strong> ₹ <?= nf($bill['SumTDS'] ?? 0) ?></div>
                <div class="col-md-3"><strong>Net Payable:</strong> ₹ <?= nf($bill['SumNetPayable'] ?? 0) ?></div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-6">🔄 Returned: <strong><?= (int)$returnedCount ?></strong> times</div>
                <div class="col-md-6">✔ Passed: <strong><?= (int)$passedCount ?></strong> times</div>
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
                            <td><?= htmlspecialchars($p['Remarks'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ATTACHED INVOICES (MORE DETAILED) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white fw-bold">📎 Attached Invoices (Detailed)</div>
        <div class="card-body">
            <?php if (count($invoices) === 0): ?>
                <p class="text-muted">No invoices attached to this bill.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-secondary">
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Vendor / Dept</th>
                            <th>HOA</th>
                            <th>PO</th>
                            <th>Sanction</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">TDS GST</th>
                            <th class="text-end">TDS IT</th>
                            <th class="text-end">Net</th>
                            <th>Bank</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($invoices as $i => $inv): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>

                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></div>
                                <div class="small text-muted">
                                    <?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?>
                                </div>
                                <div class="small">
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($inv['BillType'] ?? '-') ?></span>
                                </div>
                            </td>

                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($inv['VendorName'] ?? '-') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($inv['DeptName'] ?? '-') ?></div>
                                <div class="small">From: <?= htmlspecialchars($inv['ReceivedFromSection'] ?? '-') ?></div>
                            </td>

                            <td style="min-width:220px;">
                                <?= htmlspecialchars($inv['HOA_NAME'] ?? '-') ?>
                                <div class="small text-muted">
                                    Credit: <?= htmlspecialchars($inv['CreditName'] ?? '-') ?> |
                                    Debit: <?= htmlspecialchars($inv['DebitName'] ?? '-') ?>
                                </div>
                            </td>

                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($inv['POOrderNo'] ?? '-') ?></div>
                                <div class="small text-muted"><?= !empty($inv['POOrderDate']) ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?></div>
                                <div class="small">
                                    Base: ₹ <?= nf($inv['POAmount'] ?? 0) ?>
                                </div>
                            </td>

                            <td style="min-width:200px;">
                                <div><b>Direct:</b> <?= htmlspecialchars($inv['DirectSanctionNo'] ?? '-') ?></div>
                                <div class="small text-muted">
                                    <?= !empty($inv['DirectSanctionDate']) ? date('d-m-Y', strtotime($inv['DirectSanctionDate'])) : '-' ?>
                                </div>
                                <div class="small"><b>Mapped:</b> <?= htmlspecialchars($inv['SanctionNos'] ?? '-') ?></div>
                            </td>

                            <td class="text-end">₹ <?= nf($inv['TotalAmount'] ?? 0) ?></td>
                            <td class="text-end text-danger">₹ <?= nf($inv['TDSGSTAmount'] ?? 0) ?></td>
                            <td class="text-end text-danger">₹ <?= nf($inv['TDSITAmount'] ?? 0) ?></td>
                            <td class="text-end fw-bold text-success">₹ <?= nf($inv['NetPayable'] ?? 0) ?></td>

                            <td style="min-width:220px;">
                                <div><b><?= htmlspecialchars($inv['BankName'] ?? '-') ?></b></div>
                                <div class="small">A/C: <?= htmlspecialchars($inv['AccountNumber'] ?? '-') ?></div>
                                <div class="small">IFSC: <?= htmlspecialchars($inv['IFSC'] ?? '-') ?></div>
                                <div class="small">PAN: <?= htmlspecialchars($inv['PanNumber'] ?? '-') ?></div>
                                <div class="small">PFMS: <?= htmlspecialchars($inv['PFMSNumber'] ?? '-') ?></div>
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
        <div class="card-header bg-secondary text-white fw-bold">💳 Transaction History</div>
        <div class="card-body">
            <?php if ($transaction): ?>
                <p><strong>Transaction Date:</strong> <?= !empty($transaction['CreatedDate']) ? date('d-m-Y H:i', strtotime($transaction['CreatedDate'])) : '-' ?></p>
                <p><strong>Transaction No:</strong> <?= htmlspecialchars($transaction['TransactionNo'] ?? '-') ?></p>
                <p><strong>Batch No:</strong> <?= htmlspecialchars($transaction['BatchNo'] ?? '-') ?></p>
            <?php else: ?>
                <p class="text-muted">Transaction not initiated yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- FINAL ACCOUNTS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white fw-bold">🧾 Final Accounts / Voucher</div>
        <div class="card-body">
            <?php if ($finalAccount): ?>
                <p><strong>Voucher Generated:</strong> ✅ Yes</p>
                <p><strong>Voucher No:</strong> <?= htmlspecialchars($finalAccount['VoucherNo'] ?? '-') ?></p>
                <p><strong>Voucher Date:</strong> <?= !empty($finalAccount['CreatedDate']) ? date('d-m-Y H:i', strtotime($finalAccount['CreatedDate'])) : '-' ?></p>
            <?php else: ?>
                <p class="text-muted">Voucher not generated yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-end no-print">
        <a href="bill_entry_list.php" class="btn btn-secondary">← Back to Bills</a>
    </div>

</div>
</body>
</html>

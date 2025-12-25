<?php
include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);

// Authorization check
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);

// Check Bill ID
if (!isset($_GET['id'])) {
    die("Invalid Bill ID");
}
$billId = intval($_GET['id']);

// ===============================
// 1. Fetch Bill Master Details
// ===============================
$stmt = $conn->prepare("
    SELECT b.*, bi.BillNumber, bi.BillReceivedDate, bi.ReceivedFromSection, btm.BillType
    FROM bill_entry b
    INNER JOIN bill_initial_entry bi ON bi.Id = b.BillInitialId
    LEFT JOIN bill_type_master btm ON btm.Id = bi.BillTypeId
    WHERE b.Id = ?
");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    die("Bill not found");
}

// ===============================
// 2. Fetch Process History
// ===============================
$processStmt = $conn->prepare("
    SELECT Status, Remarks, ProcessedDate
    FROM bill_process
    WHERE BillId = ?
    ORDER BY ProcessedDate ASC
");
$processStmt->execute([$billId]);
$processHistory = $processStmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 3. Fetch Transaction
// ===============================
$txnStmt = $conn->prepare("SELECT * FROM bill_transactions WHERE BillId = ?");
$txnStmt->execute([$billId]);
$transaction = $txnStmt->fetch(PDO::FETCH_ASSOC);

// ===============================
// 4. Fetch Final Accounts
// ===============================
$finalStmt = $conn->prepare("SELECT * FROM final_accounts WHERE BillId = ?");
$finalStmt->execute([$billId]);
$finalAccount = $finalStmt->fetch(PDO::FETCH_ASSOC);

// ===============================
// 5. Fetch Attached Invoices
// ===============================
$invoiceStmt = $conn->prepare("
    SELECT i.*
    FROM bill_invoice_map bim
    INNER JOIN invoice_master i ON i.Id = bim.InvoiceId
    WHERE bim.BillInitialId = ?
");
$invoiceStmt->execute([$bill['BillInitialId']]);
$invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 6. Count Returns / Pass
// ===============================
$returnedCount = 0;
$passedCount = 0;
foreach ($processHistory as $p) {
    if ($p['Status'] === 'Returned') $returnedCount++;
    if ($p['Status'] === 'Pass')     $passedCount++;
}
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
    .btn, .no-print, .navbar,
    .sidebar { display: none !important; }
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

    <!-- BILL INFO -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            📄 Bill History – <?= htmlspecialchars($bill['BillNumber']) ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>Received Date:</strong> <?= $bill['BillReceivedDate'] ?></div>
                <div class="col-md-4"><strong>From Section:</strong> <?= htmlspecialchars($bill['ReceivedFromSection']) ?></div>
                <div class="col-md-4"><strong>Current Status:</strong>
                    <span class="badge bg-info"><?= htmlspecialchars($bill['Status']) ?></span>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">🔄 Returned: <strong><?= $returnedCount ?></strong> times</div>
                <div class="col-md-6">✔ Passed: <strong><?= $passedCount ?></strong> times</div>
            </div>
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
                                <?php if ($p['Status'] === 'Returned'): ?>
                                    <span class="badge bg-danger">Returned</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Passed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $p['ProcessedDate'] ?></td>
                            <td><?= htmlspecialchars($p['Remarks']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ATTACHED INVOICES -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white fw-bold">📎 Attached Invoices</div>
        <div class="card-body">
            <?php if (count($invoices) === 0): ?>
                <p class="text-muted">No invoices attached to this bill.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-secondary">
                        <tr>
                            <th>#</th>
                            <th>Invoice No</th>
                            <th>Invoice Date</th>
                            <th>Sanction No</th>
                            <th>Sanction Date</th>
                            <th>Vendor</th>
                            <th>Account Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $i => $inv): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($inv['InvoiceNo']) ?></td>
                            <td><?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?></td>
                             <td><?= htmlspecialchars($inv['SanctionOrderNo']) ?></td>
                            <td><?= !empty($inv['SanctionDate']) ? date('d-m-Y', strtotime($inv['SanctionDate'])) : '-' ?></td>
                            <td><?= htmlspecialchars($inv['VendorName'] ?? $inv['ReceivedFrom'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($inv['AccountDetails'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- TRANSACTION -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white fw-bold">💳 Transaction History</div>
        <div class="card-body">
            <?php if ($transaction): ?>
                <p><strong>Transaction Date:</strong> <?= $transaction['CreatedDate'] ?? '-' ?></p>
                <p><strong>Transaction No:</strong> <?= $transaction['TransactionNo'] ?? '-' ?></p>
                <p><strong>Batch No:</strong> <?= $transaction['BatchNo'] ?? '-' ?></p>
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
                <p><strong>Voucher No:</strong> <?= $finalAccount['VoucherNo'] ?? '-' ?></p>
                <p><strong>Voucher Date:</strong> <?= $finalAccount['CreatedDate'] ?? '-' ?></p>
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

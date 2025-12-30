<?php
include '../config/db.php';
include '../includes/auth.php';

$billId = intval($_GET['id'] ?? 0);
if(!$billId) exit('<div class="text-center text-danger">Invalid Bill ID</div>');

// Fetch attached invoices with full details
$invoices = $conn->prepare("
    SELECT i.*, d.DeptName
    FROM invoice_master i
    LEFT JOIN dept_master d ON i.DeptId = d.Id
    INNER JOIN bill_invoice_map bim ON i.Id = bim.InvoiceId
    WHERE bim.BillInitialId = ?
    ORDER BY i.InvoiceDate DESC
");
$invoices->execute([$billId]);
$invoices = $invoices->fetchAll(PDO::FETCH_ASSOC);

if(!$invoices){
    echo '<div class="text-center text-muted">No invoices attached to this bill.</div>';
    exit;
}

foreach($invoices as $inv):
?>
<div class="invoice-card shadow-sm mb-4 p-3 rounded">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center invoice-header mb-2" style="background:#007bff;color:#fff;padding:10px 15px;border-radius:5px;">
        <div>
            <strong>Invoice #: <?= htmlspecialchars($inv['InvoiceNo']) ?></strong> 
            | <?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?>
        </div>
        <button class="btn btn-light btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
    </div>

    <!-- Vendor & Department -->
    <div class="row mb-2">
        <div class="col-md-4"><strong>Vendor:</strong> <?= htmlspecialchars($inv['VendorName']) ?></div>
        <div class="col-md-4"><strong>Department:</strong> <?= htmlspecialchars($inv['DeptName']) ?></div>
        <div class="col-md-4"><strong>Invoice Status:</strong> <?= htmlspecialchars($inv['Status'] ?? 'DRAFT') ?></div>
    </div>

    <!-- Financial Details -->
    <div class="row mb-2">
        <div class="col-md-4"><strong>Total Amount:</strong> ₹ <?= number_format($inv['TotalAmount'],2) ?></div>
        <div class="col-md-4"><strong>IT Deduction:</strong> ₹ <?= number_format($inv['IT'] ?? 0,2) ?></div>
        <div class="col-md-4"><strong>GST:</strong> ₹ <?= number_format($inv['GST'] ?? 0,2) ?></div>
    </div>

    <!-- PO & PFMS -->
    <div class="row mb-2">
        <div class="col-md-4"><strong>PO Order No:</strong> <?= htmlspecialchars($inv['POOrderNo']) ?></div>
        <div class="col-md-4"><strong>PO Date:</strong> <?= $inv['POOrderDate'] ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?></div>
        <div class="col-md-4"><strong>PFMS No:</strong> <?= htmlspecialchars($inv['PFMSUniqueNo']) ?></div>
    </div>

    <!-- Additional Details -->
    <div class="row">
        <div class="col-md-6"><strong>Invoice Date:</strong> <?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></div>
        <div class="col-md-6"><strong>Remarks:</strong> <?= htmlspecialchars($inv['Remarks'] ?? '-') ?></div>
    </div>
</div>
<?php endforeach; ?>

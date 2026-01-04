<?php
include '../config/db.php';
include '../includes/auth.php';

$billId = intval($_GET['id'] ?? 0);
if(!$billId) exit('<div class="text-center text-danger">Invalid Bill ID</div>');

// Fetch attached invoices with full details
$invoices = $conn->prepare("
    SELECT i.*, d.DeptName, b.BillType, c.CreditName, de.DebitName,
           h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName AS HOA_NAME
    FROM invoice_master i
    LEFT JOIN dept_master d ON i.DeptId = d.Id
    LEFT JOIN bill_type_master b ON i.BillTypeId = b.Id
    LEFT JOIN account_credit_master c ON i.CreditToId = c.Id
    LEFT JOIN account_debit_master de ON i.DebitFromId = de.Id
    LEFT JOIN hoa_master h ON i.HOAId = h.HoaId
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

// Helper for formatting numbers
function nf($v){ return number_format($v,2); }

foreach($invoices as $inv):
?>
<div class="invoice-card shadow-sm mb-4 p-3 rounded">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center invoice-header mb-2">
        <div>
            <strong>Invoice #: <?= htmlspecialchars($inv['InvoiceNo']) ?></strong> 
            | <?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?>
        </div>
        <button class="btn btn-light btn-sm" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info-<?= $inv['Id'] ?>">Info</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-amount-<?= $inv['Id'] ?>">Amount</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-po-<?= $inv['Id'] ?>">PO & TDS</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank-<?= $inv['Id'] ?>">Bank & Other</button></li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: General Info -->
        <div class="tab-pane fade show active" id="tab-info-<?= $inv['Id'] ?>">
            <div class="row mb-2">
                <div class="col-md-4"><strong>Vendor:</strong> <?= htmlspecialchars($inv['VendorName']) ?></div>
                <div class="col-md-4"><strong>Department:</strong> <?= htmlspecialchars($inv['DeptName']) ?></div>
                <div class="col-md-4"><strong>HOA:</strong> <?= $inv['HOA_NAME'] ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Bill Type:</strong> <?= $inv['BillType'] ?></div>
                <div class="col-md-4"><strong>Invoice Status:</strong> <?= htmlspecialchars($inv['Status'] ?? 'DRAFT') ?></div>
                <div class="col-md-4"><strong>Invoice Date:</strong> <?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Sanction Order:</strong> <?= $inv['SanctionOrderNo'] ?></div>
                <div class="col-md-4"><strong>Sanction Date:</strong> <?= $inv['SanctionDate'] ? date('d-m-Y', strtotime($inv['SanctionDate'])) : '-' ?></div>
                <div class="col-md-4"><strong>PO Order:</strong> <?= $inv['POOrderNo'] ?></div>
            </div>
        </div>

        <!-- TAB 2: Amount & Calculations -->
        <div class="tab-pane fade" id="tab-amount-<?= $inv['Id'] ?>">
            <div class="row mb-2">
                <div class="col-md-3"><strong>Amount:</strong> ₹ <?= nf($inv['Amount']) ?></div>
                <div class="col-md-3"><strong>GST (<?= $inv['GSTPercent'] ?>%):</strong> ₹ <?= nf($inv['GSTAmount']) ?></div>
                <div class="col-md-3"><strong>IT (<?= $inv['ITPercent'] ?>%):</strong> ₹ <?= nf($inv['ITAmount']) ?></div>
                <div class="col-md-3"><strong>Total Amount:</strong> ₹ <?= nf($inv['TotalAmount']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"><strong>TDS GST (<?= $inv['TDSGSTPercent'] ?>%):</strong> ₹ <?= nf($inv['TDSGSTAmount']) ?></div>
                <div class="col-md-3"><strong>TDS IT (<?= $inv['TDSITPercent'] ?>%):</strong> ₹ <?= nf($inv['TDSITAmount']) ?></div>
                <div class="col-md-3"><strong>Net Payable:</strong> ₹ <?= nf($inv['NetPayable']) ?></div>
            </div>
        </div>

        <!-- TAB 3: PO & TDS -->
        <div class="tab-pane fade" id="tab-po-<?= $inv['Id'] ?>">
            <div class="row mb-2">
                <div class="col-md-3"><strong>PO Amount:</strong> ₹ <?= nf($inv['POAmount']) ?></div>
                <div class="col-md-3"><strong>PO GST (<?= $inv['POGSTPercent'] ?>%):</strong> ₹ <?= nf($inv['POGSTAmount']) ?></div>
                <div class="col-md-3"><strong>PO IT (<?= $inv['POITPercent'] ?>%):</strong> ₹ <?= nf($inv['POITAmount']) ?></div>
                <div class="col-md-3"><strong>PO Total:</strong> ₹ <?= nf($inv['POAmount'] + $inv['POGSTAmount'] + $inv['POITAmount']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"><strong>TDS PO GST (<?= $inv['TDSPoGSTPercent'] ?>%):</strong> ₹ <?= nf($inv['TDSPoGSTAmount']) ?></div>
                <div class="col-md-3"><strong>TDS PO IT (<?= $inv['TDSPoITPercent'] ?>%):</strong> ₹ <?= nf($inv['TDSPoITAmount']) ?></div>
                <div class="col-md-3"><strong>PO Net Payable:</strong> ₹ <?= nf($inv['POAmount'] + $inv['POGSTAmount'] + $inv['POITAmount'] - ($inv['TDSPoGSTAmount'] + $inv['TDSPoITAmount'])) ?></div>
            </div>
        </div>

        <!-- TAB 4: Bank & Other -->
        <div class="tab-pane fade" id="tab-bank-<?= $inv['Id'] ?>">
            <div class="row mb-2">
                <div class="col-md-4"><strong>Bank Name:</strong> <?= htmlspecialchars($inv['BankName']) ?></div>
                <div class="col-md-4"><strong>IFSC:</strong> <?= htmlspecialchars($inv['IFSC']) ?></div>
                <div class="col-md-4"><strong>Account No:</strong> <?= htmlspecialchars($inv['AccountNumber']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Received From Section:</strong> <?= htmlspecialchars($inv['ReceivedFromSection']) ?></div>
                <div class="col-md-4"><strong>Section DA Name:</strong> <?= htmlspecialchars($inv['SectionDAName']) ?></div>
                <div class="col-md-4"><strong>PFMS No:</strong> <?= htmlspecialchars($inv['PFMSUniqueNo']) ?></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4"><strong>Credit To:</strong> <?= $inv['CreditName'] ?></div>
                <div class="col-md-4"><strong>Debit From:</strong> <?= $inv['DebitName'] ?></div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

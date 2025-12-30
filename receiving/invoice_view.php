<?php
include '../config/db.php';
include '../includes/auth.php';

$id = $_GET['id'] ?? 0;
$id = intval($id);

$stmt = $conn->prepare("
    SELECT im.*, 
           fy.FinYear, 
           d.DeptName, 
           b.BillType, 
           c.CreditName, 
           de.DebitName, 
           h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName AS HOA_NAME
    FROM invoice_master im
    LEFT JOIN fin_year_master fy ON im.FinancialYearId = fy.Id
    LEFT JOIN dept_master d ON im.DeptId = d.Id
    LEFT JOIN bill_type_master b ON im.BillTypeId = b.Id
    LEFT JOIN account_credit_master c ON im.CreditToId = c.Id
    LEFT JOIN account_debit_master de ON im.DebitFromId = de.Id
    LEFT JOIN hoa_master h ON im.HOAId = h.HoaId
    WHERE im.Id = ?
");
$stmt->execute([$id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$inv){
    echo '<div class="text-danger text-center">Invoice not found.</div>';
    exit;
}
?>

<ul class="nav nav-tabs mb-3" id="invoiceTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">Invoice Info</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="amount-tab" data-bs-toggle="tab" data-bs-target="#amount" type="button" role="tab">Amounts & Calculations</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab">Bank & Other Details</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="info" role="tabpanel">
        <div class="row">
            <div class="col-md-4"><strong>Invoice No:</strong> <?= $inv['InvoiceNo'] ?></div>
            <div class="col-md-4"><strong>Invoice Date:</strong> <?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></div>
            <div class="col-md-4"><strong>Vendor:</strong> <?= $inv['VendorName'] ?></div>
            <div class="col-md-4"><strong>Financial Year:</strong> <?= $inv['FinYear'] ?></div>
            <div class="col-md-4"><strong>Department:</strong> <?= $inv['DeptName'] ?></div>
            <div class="col-md-4"><strong>HOA:</strong> <?= $inv['HOA_NAME'] ?></div>
            <div class="col-md-4"><strong>Sanction Order No:</strong> <?= $inv['SanctionOrderNo'] ?></div>
            <div class="col-md-4"><strong>Sanction Date:</strong> <?= $inv['SanctionDate'] ? date('d-m-Y', strtotime($inv['SanctionDate'])) : '-' ?></div>
            <div class="col-md-4"><strong>PO Order No:</strong> <?= $inv['POOrderNo'] ?></div>
            <div class="col-md-4"><strong>PO Order Date:</strong> <?= $inv['POOrderDate'] ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?></div>
        </div>
    </div>
    <div class="tab-pane fade" id="amount" role="tabpanel">
        <div class="row">
            <div class="col-md-3"><strong>Amount:</strong> <?= number_format($inv['Amount'],2) ?></div>
            <div class="col-md-3"><strong>GST %:</strong> <?= $inv['GSTPercent'] ?></div>
            <div class="col-md-3"><strong>GST Amount:</strong> <?= number_format($inv['GSTAmount'],2) ?></div>
            <div class="col-md-3"><strong>IT %:</strong> <?= $inv['ITPercent'] ?></div>
            <div class="col-md-3"><strong>IT Amount:</strong> <?= number_format($inv['ITAmount'],2) ?></div>
            <div class="col-md-3"><strong>TDS:</strong> <?= number_format($inv['TDS'],2) ?></div>
            <div class="col-md-3"><strong>Total Amount:</strong> <?= number_format($inv['TotalAmount'],2) ?></div>
        </div>
    </div>
    <div class="tab-pane fade" id="bank" role="tabpanel">
        <div class="row">
            <div class="col-md-4"><strong>Bank Name:</strong> <?= $inv['BankName'] ?></div>
            <div class="col-md-4"><strong>IFSC:</strong> <?= $inv['IFSC'] ?></div>
            <div class="col-md-4"><strong>Account No:</strong> <?= $inv['AccountNumber'] ?></div>
            <div class="col-md-4"><strong>Received From Section:</strong> <?= $inv['ReceivedFromSection'] ?></div>
            <div class="col-md-4"><strong>Section DA Name:</strong> <?= $inv['SectionDAName'] ?></div>
            <div class="col-md-4"><strong>Bill Type:</strong> <?= $inv['BillType'] ?></div>
            <div class="col-md-4"><strong>PFMS Unique No:</strong> <?= $inv['PFMSUniqueNo'] ?></div>
            <div class="col-md-4"><strong>Credit To:</strong> <?= $inv['CreditName'] ?></div>
            <div class="col-md-4"><strong>Debit From:</strong> <?= $inv['DebitName'] ?></div>
        </div>
    </div>
</div>

<script>
// Update "View Full Page" link dynamically
$('#fullPageView').attr('href', 'invoice_full_view.php?id=<?= $inv['Id'] ?>');
</script>

<?php
include '../config/db.php';
include '../includes/auth.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("
    SELECT im.*,so.SanctionOrderNo,so.SanctionDate,so.SanctionAmount,so.GSTPercent,so.GSTAmount
    ,so.ITPercent,so.ITAmount,so.SanctionNetAmount,pm.POOrderNo,pm.POOrderDate,pm.POAmount,pm.POGSTPercent,pm.POITPercent,pm.PONetAmount,
           fy.FinYear,
           d.DeptName,
           b.BillType,
           c.CreditName,
           de.DebitName,
           h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName AS HOA_NAME
    FROM invoice_master im
    left join sanction_order_master so on so.Id = im.SanctionId
    left join po_master pm on pm.Id = im.POId
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
    echo '<div class="text-danger text-center">Invoice not found</div>';
    exit;
}

function nf($v){
    return number_format((float)($v ?? 0), 2);
}

$poAmount = (float) ($inv['POAmount'] ?? 0);

$poGSTPercent = (float) ($inv['POGSTPercent'] ?? 0);
$poITPercent  = (float) ($inv['POITPercent'] ?? 0);

$poGSTAmount = ($poAmount * $poGSTPercent) / 100;
$poITAmount  = ($poAmount * $poITPercent) / 100;

/* ---- TDS % (use DB value or default 0) ---- */
$tdsPoGSTPercent = (float) ($inv['TDSPoGSTPercent'] ?? 0);
$tdsPoITPercent  = (float) ($inv['TDSPoITPercent'] ?? 0);

$tdsPoGSTAmount = ($poGSTAmount * $tdsPoGSTPercent) / 100;
$tdsPoITAmount  = ($poITAmount * $tdsPoITPercent) / 100;

$poTotal = $poAmount + $poGSTAmount + $poITAmount;
$poNetPayable = $poTotal - ($tdsPoGSTAmount + $tdsPoITAmount);
?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info">Invoice Info</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-amount">Amounts & Calculations</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-po">PO Calculations</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bank">Bank & Other Details</button>
    </li>
</ul>

<div class="tab-content">

<!-- TAB 1 -->
<div class="tab-pane fade show active" id="tab-info">
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

<!-- TAB 2 -->
<div class="tab-pane fade" id="tab-amount">
<table class="table table-bordered table-sm">
<tr><th>Base Amount</th><td class="text-end"><?= nf($inv['Amount']) ?></td></tr>
<tr><th>GST (<?= $inv['GSTPercent'] ?>%)</th><td class="text-end"><?= nf($inv['GSTAmount']) ?></td></tr>
<tr><th>IT (<?= $inv['ITPercent'] ?>%)</th><td class="text-end"><?= nf($inv['ITAmount']) ?></td></tr>
<tr class="table-secondary fw-bold"><th>Total Amount</th><td class="text-end"><?= nf($inv['TotalAmount']) ?></td></tr>
<tr><th>TDS GST (<?= $inv['TDSGSTPercent'] ?>%)</th><td class="text-end"><?= nf($inv['TDSGSTAmount']) ?></td></tr>
<tr><th>TDS IT (<?= $inv['TDSITPercent'] ?>%)</th><td class="text-end"><?= nf($inv['TDSITAmount']) ?></td></tr>
<tr class="table-success fw-bold"><th>Net Payable</th><td class="text-end"><?= nf($inv['NetPayable']) ?></td></tr>
</table>
</div>

<!-- TAB 3 -->
<div class="tab-pane fade" id="tab-po">
<table class="table table-bordered table-sm">

<tr>
    <th>PO Amount</th>
    <td class="text-end"><?= nf($poAmount) ?></td>
</tr>

<tr>
    <th>PO GST (<?= $poGSTPercent ?>%)</th>
    <td class="text-end"><?= nf($poGSTAmount) ?></td>
</tr>

<tr>
    <th>PO IT (<?= $poITPercent ?>%)</th>
    <td class="text-end"><?= nf($poITAmount) ?></td>
</tr>

<tr class="table-secondary fw-bold">
    <th>PO Total Amount</th>
    <td class="text-end"><?= nf($poTotal) ?></td>
</tr>

<tr>
    <th>TDS PO GST (<?= $tdsPoGSTPercent ?>%)</th>
    <td class="text-end"><?= nf($tdsPoGSTAmount) ?></td>
</tr>

<tr>
    <th>TDS PO IT (<?= $tdsPoITPercent ?>%)</th>
    <td class="text-end"><?= nf($tdsPoITAmount) ?></td>
</tr>

<tr class="table-success fw-bold">
    <th>PO Net Payable</th>
    <td class="text-end"><?= nf($poNetPayable) ?></td>
</tr>

</table>
</div>

<!-- TAB 4 -->
<div class="tab-pane fade" id="tab-bank">
<div class="row">
    <div class="col-md-4"><strong>Bank Name:</strong> <?= $inv['BankName'] ?></div>
    <div class="col-md-4"><strong>IFSC:</strong> <?= $inv['IFSC'] ?></div>
    <div class="col-md-4"><strong>Account No:</strong> <?= $inv['AccountNumber'] ?></div>

    <div class="col-md-4"><strong>Received From:</strong> <?= $inv['ReceivedFromSection'] ?></div>
    <div class="col-md-4"><strong>Section DA:</strong> <?= $inv['SectionDAName'] ?></div>

    <div class="col-md-4"><strong>Credit To:</strong> <?= $inv['CreditName'] ?></div>
    <div class="col-md-4"><strong>Debit From:</strong> <?= $inv['DebitName'] ?></div>
</div>
</div>

</div>


<script>
// Update "View Full Page" link dynamically
$('#fullPageView').attr('href', 'invoice_full_view.php?id=<?= $inv['Id'] ?>');
</script>

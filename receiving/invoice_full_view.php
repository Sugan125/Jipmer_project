<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    echo '<div class="text-danger text-center mt-5">Invoice not found.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice No <?= $inv['InvoiceNo'] ?></title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        body { padding: 20px; background: #f7f7f7; }
        .invoice-container { max-width: 900px; margin: auto; background: #fff; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,.15); }
        .invoice-header { border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 20px; }
        .invoice-header h2 { color: #007bff; }
        .section-title { background: #007bff; color: #fff; padding: 5px 10px; margin-bottom: 10px; }
        .table th, .table td { vertical-align: middle !important; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="invoice-container">
    <!-- Header -->
    <div class="invoice-header text-center">
        <h2>Invoice Details</h2>
        <small>Invoice No: <?= $inv['InvoiceNo'] ?> | Date: <?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></small>
    </div>

    <!-- Invoice Info -->
    <div class="section-title">Invoice Information</div>
    <div class="row mb-3">
        <div class="col-md-4"><strong>Financial Year:</strong> <?= $inv['FinYear'] ?></div>
        <div class="col-md-4"><strong>HOA:</strong> <?= $inv['HOA_NAME'] ?></div>
        <div class="col-md-4"><strong>Department:</strong> <?= $inv['DeptName'] ?></div>
        <div class="col-md-4"><strong>Vendor:</strong> <?= $inv['VendorName'] ?></div>
        <div class="col-md-4"><strong>Sanction Order No:</strong> <?= $inv['SanctionOrderNo'] ?></div>
        <div class="col-md-4"><strong>Sanction Date:</strong> <?= $inv['SanctionDate'] ? date('d-m-Y', strtotime($inv['SanctionDate'])) : '-' ?></div>
        <div class="col-md-4"><strong>PO Order No:</strong> <?= $inv['POOrderNo'] ?></div>
        <div class="col-md-4"><strong>PO Order Date:</strong> <?= $inv['POOrderDate'] ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?></div>
        <div class="col-md-4"><strong>Bill Type:</strong> <?= $inv['BillType'] ?></div>
    </div>

    <!-- Amounts -->
    <div class="section-title">Amounts & Calculations</div>
    <table class="table table-bordered">
        <thead class="table-primary">
            <tr>
                <th>Amount</th>
                <th>GST %</th>
                <th>GST Amount</th>
                <th>IT %</th>
                <th>IT Amount</th>
                <th>TDS</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= number_format($inv['Amount'],2) ?></td>
                <td><?= $inv['GSTPercent'] ?></td>
                <td><?= number_format($inv['GSTAmount'],2) ?></td>
                <td><?= $inv['ITPercent'] ?></td>
                <td><?= number_format($inv['ITAmount'],2) ?></td>
                <td><?= number_format($inv['TDS'],2) ?></td>
                <td><?= number_format($inv['TotalAmount'],2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Bank & Other Details -->
    <div class="section-title">Bank & Other Details</div>
    <div class="row">
        <div class="col-md-4"><strong>Bank Name:</strong> <?= $inv['BankName'] ?></div>
        <div class="col-md-4"><strong>IFSC:</strong> <?= $inv['IFSC'] ?></div>
        <div class="col-md-4"><strong>Account Number:</strong> <?= $inv['AccountNumber'] ?></div>
        <div class="col-md-4"><strong>Received From Section:</strong> <?= $inv['ReceivedFromSection'] ?></div>
        <div class="col-md-4"><strong>Section DA Name:</strong> <?= $inv['SectionDAName'] ?></div>
        <div class="col-md-4"><strong>Credit To:</strong> <?= $inv['CreditName'] ?></div>
        <div class="col-md-4"><strong>Debit From:</strong> <?= $inv['DebitName'] ?></div>
    </div>

    <!-- Print Button -->
    <div class="text-center mt-4 no-print">
        <button onclick="window.print();" class="btn btn-primary"><i class="fa fa-print"></i> Print Invoice</button>
        <a href="invoice_list.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to List</a>
    </div>
</div>
</body>
</html>

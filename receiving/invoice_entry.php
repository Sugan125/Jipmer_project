<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';

// Fetch Masters
$finYears = $conn->query("SELECT Id, FinYear, IsCurrent FROM fin_year_master WHERE Status = 1")->fetchAll();
$dept = $conn->query("SELECT Id, DeptName FROM dept_master WHERE Status=1")->fetchAll();
$billType = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1")->fetchAll();
$credit = $conn->query("SELECT Id, CreditName FROM account_credit_master WHERE Status=1")->fetchAll();
$debit = $conn->query("SELECT Id, DebitName FROM account_debit_master WHERE Status=1")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>Invoice Master Entry</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1200px;margin:auto;}
.section-card{border:1px solid #dee2e6;border-radius:8px;padding:15px;margin-bottom:20px;background:#f9f9f9;}
.section-title{font-weight:600;color:#007bff;margin-bottom:15px;}
small { color:green; font-weight: 600 }
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">
<h4 class="text-primary mb-4"><i class="fa fa-file-invoice"></i> Invoice Master Entry</h4>

<form id="invoiceForm">

<!-- Section 1: Masters & Vendor -->
<div class="section-card">
<div class="section-title">General Details</div>
<div class="row g-3">
    <div class="col-md-3">
        <label>Financial Year</label>
        <select name="FinancialYearId" id="finYear" class="form-select" required>
            <option value="">-- Select Financial Year --</option>
            <?php foreach($finYears as $f): ?>
                <option value="<?= $f['Id'] ?>" <?= $f['IsCurrent'] ? 'selected' : '' ?>><?= $f['FinYear'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <label>HOA</label>
        <select name="HOAId" id="hoa" class="form-select" required>
            <option value="">-- Select HOA --</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Department</label>
        <select name="DeptId" class="form-select" required>
            <option value="">-- Select Department --</option>
            <?php foreach($dept as $d): ?>
                <option value="<?= $d['Id'] ?>"><?= $d['DeptName'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <label>Vendor Name</label>
        <input type="text" name="VendorName" class="form-control" required>
    </div>
</div>
</div>

<!-- Section 2: Invoice & Sanction -->
<div class="section-card">
<div class="section-title">Invoice & Sanction Details</div>
<div class="row g-3">
    <div class="col-md-3"><label>Invoice No</label><input name="InvoiceNo" class="form-control" required></div>
    <div class="col-md-3"><label>Invoice Date</label><input type="date" name="InvoiceDate" class="form-control" required></div>
    <div class="col-md-3"><label>Sanction Order No</label><input type="text" name="SanctionOrderNo" class="form-control"></div>
    <div class="col-md-3"><label>Sanction Date</label><input type="date" name="SanctionDate" class="form-control"></div>
    <div class="col-md-3"><label>PO Order No</label><input name="POOrderNo" class="form-control"></div>
    <div class="col-md-3"><label>PO Order Date</label><input type="date" name="POOrderDate" class="form-control"></div>
</div>
</div>

<div class="section-card">
<div class="section-title">PO Details</div>
<div class="row g-3">

<div class="col-md-3">
<label>PO Amount</label>
<input type="number" step="0.01" id="po_amount" name="POAmount" class="form-control">
<small class="text-muted" id="po_base"></small>
</div>

<div class="col-md-3">
<label>PO GST %</label>
<input type="number" max="100" step="0.01" id="po_gst_p" name="POGSTPercent" class="form-control">
<small id="po_gst_amt"></small>
</div>

<div class="col-md-3">
<label>PO IT %</label>
<input type="number" max="100" step="0.01" id="po_it_p" name="POITPercent" class="form-control">
<small id="po_it_amt"></small>
</div>

<div class="col-md-3">
<label>TDS GST %</label>
<input type="number" max="100" step="0.01" id="po_tds_gst_p" name="TDSPoGSTPercent" class="form-control">
<small id="po_tds_gst_amt"></small>
</div>

<div class="col-md-3">
<label>TDS IT % (2 or 10)</label>
<input type="number" id="po_tds_it_p" name="TDSPoITPercent" class="form-control">
<small id="po_tds_it_amt"></small>
</div>

<div class="col-md-3">
<label>PO Net Total</label>
<input readonly id="po_net_total" class="form-control">
</div>

</div>
</div>

<!-- Section 3: Amounts & Calculations -->
<div class="section-card">
<div class="section-title">Amount Details & Calculations</div>
<div class="row g-3">

<div class="col-md-3">
<label>Amount</label>
<input type="number" step="0.01" id="amount" name="Amount" class="form-control">
</div>

<div class="col-md-3">
<label>GST %</label>
<input type="number" step="0.01" id="gstp" name="GSTPercent" class="form-control">
<small id="gst_amt"></small>
</div>

<div class="col-md-3">
<label>IT %</label>
<input type="number" step="0.01" id="itp" name="ITPercent" class="form-control">
<small id="it_amt"></small>
</div>

<div class="col-md-3">
<label>TDS GST %</label>
<input type="number" step="0.01" id="tds_gst_p" name="TDSGSTPercent" class="form-control">
<small id="tds_gst_amt"></small>
</div>

<div class="col-md-3">
<label>TDS IT % (2 or 10)</label>
<input type="number" id="tds_it_p" name="TDSITPercent" class="form-control">
<small id="tds_it_amt"></small>
</div>

<div class="col-md-3">
<label>Invoice Total</label>
<input readonly id="invoice_total" class="form-control">
</div>

<div class="col-md-3">
<label>TDS Total</label>
<input readonly id="tds_total" class="form-control">
</div>

<div class="col-md-3">
<label>Net Payable</label>
<input readonly id="net_payable" name="NetPayable" class="form-control">
</div>

</div>
</div>

<!-- Section 4: Bill Type & Sections -->
<div class="section-card">
<div class="section-title">Bill & Section Details</div>
<div class="row g-3">
    <div class="col-md-3">
        <label>Bill Type</label>
        <select name="BillTypeId" class="form-select">
            <option value="">-- Select Bill Type --</option>
            <?php foreach($billType as $b): ?>
                <option value="<?= $b['Id'] ?>"><?= $b['BillType'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><label>Received From Section</label><input name="ReceivedFromSection" class="form-control"></div>
    <div class="col-md-3"><label>Section DA Name</label><input name="SectionDAName" class="form-control"></div>
    <div class="col-md-3"><label>PFMS Unique No</label><input name="PFMSUniqueNo" class="form-control"></div>
</div>
</div>

<!-- Section 5: Bank & Accounts -->
<div class="section-card">
<div class="section-title">Bank & Account Details</div>
<div class="row g-3">
    <div class="col-md-3"><label>Bank Name</label><input name="BankName" class="form-control"></div>
    <div class="col-md-3"><label>IFSC</label><input name="IFSC" class="form-control"></div>
    <div class="col-md-3"><label>Account Number</label><input name="AccountNumber" class="form-control"></div>
    <div class="col-md-3">
        <label>Credit To</label>
        <select name="CreditToId" class="form-select">
            <option value="">-- Select Credit Account --</option>
            <?php foreach($credit as $c): ?>
                <option value="<?= $c['Id'] ?>"><?= $c['CreditName'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Debit From</label>
        <select name="DebitFromId" class="form-select">
            <option value="">-- Select Debit Account --</option>
            <?php foreach($debit as $d): ?>
                <option value="<?= $d['Id'] ?>"><?= $d['DebitName'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
</div>

<button class="btn btn-success mt-3">Save Invoice</button>
</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
// Load HOA dynamically
function loadHOA(finYearId){
    $('#hoa').html('<option value="">Loading...</option>');
    $.getJSON('../processing/get_hoa_by_fy.php', { FinYearId: finYearId }, function(res){
        let opt = '<option value="">-- Select HOA --</option>';
        $.each(res, function(i, row){
            opt += `<option value="${row.id}">${row.text}</option>`;
        });
        $('#hoa').html(opt);
    });
}
$('#finYear').change(function(){ let fy = $(this).val(); if(fy) loadHOA(fy); else $('#hoa').html('<option value="">-- Select HOA --</option>'); });
$(document).ready(function(){ let defaultFY = $('#finYear').val(); if(defaultFY) loadHOA(defaultFY); });

// Amount Calculations
function calc(){
    let amt=parseFloat($('#amount').val())||0;
    let gstp=parseFloat($('#gstp').val())||0;
    let itp=parseFloat($('#itp').val())||0;
    let tds=parseFloat($('#tds').val())||0;
    let gst=amt*gstp/100;
    let it=amt*itp/100;
    $('#total').val((amt+gst-it-tds).toFixed(2));
}
$('#amount,#gstp,#itp,#tds').on('input',calc);
function percentCalc(base,p){ return (base*p/100)||0; }

function calcPO(){
 let a=+$('#po_amount').val()||0;
 let gst=percentCalc(a,+$('#po_gst_p').val());
 let it=percentCalc(a,+$('#po_it_p').val());
 let tdsG=percentCalc(a,+$('#po_tds_gst_p').val());
 let tdsI=percentCalc(a,+$('#po_tds_it_p').val());

 
 $('#po_gst_amt').text('GST: '+gst.toFixed(2));
 $('#po_it_amt').text('IT: '+it.toFixed(2));
 $('#po_tds_gst_amt').text('TDS GST: '+tdsG.toFixed(2));
 $('#po_tds_it_amt').text('TDS IT: '+tdsI.toFixed(2));

 $('#po_net_total').val((a+gst+it-tdsG-tdsI).toFixed(2));
}
$('#po_tds_it_p').on('blur', function () {
    let val = +this.value;

    if (val !== 2 && val !== 10 && val !== 0 && this.value !== '') {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid TDS IT %',
            text: 'Only 2% or 10% is allowed'
        });
        this.value = '';
        this.focus();
    }
});
function calcInvoice(){
 let a=+$('#amount').val()||0;
 let gst=percentCalc(a,+$('#gstp').val());
 let it=percentCalc(a,+$('#itp').val());
 let tdsG=percentCalc(a,+$('#tds_gst_p').val());
 let tdsI=percentCalc(a,+$('#tds_it_p').val());



 let total=a+gst+it;
 let tdsTotal=tdsG+tdsI;

 $('#gst_amt').text('GST: '+gst.toFixed(2));
 $('#it_amt').text('IT: '+it.toFixed(2));
 $('#tds_gst_amt').text('TDS GST: '+tdsG.toFixed(2));
 $('#tds_it_amt').text('TDS IT: '+tdsI.toFixed(2));

 $('#invoice_total').val(total.toFixed(2));
 $('#tds_total').val(tdsTotal.toFixed(2));
 $('#net_payable').val((total-tdsTotal).toFixed(2));
}
$('#tds_it_p').on('blur', function () {
    let val = +this.value;

    if (val !== 2 && val !== 10 && val !== 0 && this.value !== '') {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid TDS IT %',
            text: 'Only 2% or 10% is allowed'
        });
        this.value = '';
        this.focus();
    }
});
$('input').on('input',function(){ calcPO(); calcInvoice(); });
// Form Submit
$('#invoiceForm').submit(function(e){
 e.preventDefault();
 $.post('invoice_submit.php',$(this).serialize(),function(r){
  if(r.status==='success'){
   Swal.fire({
                title: 'Saved',
                text: 'Invoice created successfully',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                // Redirect to invoice list page after user clicks OK
                window.location.href = 'invoice_list.php';
            });
  }else Swal.fire('Error',r.message,'error');
 },'json');
});
</script>
</body>
</html>

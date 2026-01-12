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
.gst_msg { color:#ffb300;; font-size: 12px;}
/* #sanction_id {
    min-height: 120px; --multiple select
} */
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
        <select name="DeptId" id="dept_id" class="form-select" required>
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
    <div class="col-md-3"><label>Invoice No</label><input name="InvoiceNo" id="invoice_no" class="form-control" required></div>
    <div class="col-md-3"><label>Invoice Date</label><input type="date" name="InvoiceDate" class="form-control" required></div>
</div>
</div>

<!-- Section: PO & Sanction Mapping -->
<div class="section-card">
<div class="section-title">PO & Sanction Mapping</div>

<div class="row g-3">
    <div class="col-md-4">
        <label>Purchase Order</label>
        <select name="POId" id="po_id" class="form-select" required>
            <option value="">-- Select PO --</option>
        </select>
    </div>

    

    <div class="col-md-4">
    <label>Sanction Order</label>
        <select name="SanctionId" id="sanction_id" class="form-select" required>
            <option value="">-- Select Sanction --</option>
        </select>
        <!-- <select name="SanctionId[]" id="sanction_id" class="form-select" multiple required>  --multiple select
        
        </select>

        <small class="text-muted">
            Hold Ctrl (or Cmd) to select multiple sanctions
        </small> -->
    </div>

    <div class="col-md-4">
    <label>Selected Sanction Amount</label>
    <input readonly id="sanction_balance" class="form-control fw-bold text-success">
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-4">
        <label>Total Sanction Amount</label>
        <input id="po_total_sanction" class="form-control fw-bold text-primary" readonly>
    </div>

    <div class="col-md-4">
        <label>Already Billed Amount</label>
        <input id="po_billed_amount" class="form-control fw-bold text-danger" readonly>
    </div>

    <!-- <div class="col-md-4">
        <label>Available PO Balance</label>
        <input id="po_available_balance" class="form-control fw-bold text-success" readonly>
    </div> -->

    <div class="col-md-4">
        <label>Available sanction Balance</label>
        <input id="sanction_available_balance" class="form-control fw-bold text-success" readonly>
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
<div class="invalid-feedback">
    Invoice amount cannot exceed selected sanction balance.
</div>
</div>

<div class="col-md-3">
<label>GST %</label>
<input type="number" step="0.01" id="gstp" name="GSTPercent" class="form-control">
<small id="gst_amt"></small><br>
<span id="gst_span" class="gst_msg"></span>
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
    <div class="col-md-3">
    <label>Received From Section</label>
    <input name="ReceivedFromSection" id="received_section" class="form-control" readonly>
    </div>
    <div class="col-md-3">
    <label>Section DA Name</label>
    <input name="SectionDAName" id="section_da" class="form-control" readonly>
</div>
</div>
</div>

<!-- Section 5: Bank & Accounts -->
<div class="section-card">
<div class="section-title">Bank & Account Details</div>
<div class="row g-3">
     <div class="col-md-3"><label>PAN Number</label><input name="PanNumber" class="form-control"></div>
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
const LOGGED_IN_USER = "<?= $_SESSION['empname'] ?>";

$(document).ready(function () {
    $('#section_da').val(LOGGED_IN_USER);
});

let isInvoiceDuplicate = false;

$('#invoice_no').on('blur', function () {

    let invoiceNo = this.value.trim();
    if (!invoiceNo) return;

    $.getJSON('check_invoice_no.php', {
        InvoiceNo: invoiceNo
    }, function (res) {

        if (res.exists) {
            isInvoiceDuplicate = true;

            Swal.fire({
                icon: 'error',
                title: 'Duplicate Invoice Number',
                text: 'This invoice number already exists.'
            }).then(() => {
                  $('#invoice_no')
                    .val('')  
                    .focus();  
            });

        } else {
            isInvoiceDuplicate = false;
        }
    });
});

$('#dept_id').change(function () {
let deptName = $('#dept_id option:selected').text();
$('#received_section').val(deptName);
});

let poAvailableBalance = 0;
let selectedSanctionBalance = 0;
/* ================= LOAD HOA ================= */
function loadHOA(finYearId){
    $('#hoa').html('<option>Loading...</option>');
    $.getJSON('../processing/get_hoa_by_fy.php', { FinYearId: finYearId }, function(res){
        let opt = '<option value="">-- Select HOA --</option>';
        $.each(res, function(i,row){
            opt += `<option value="${row.id}">${row.text}</option>`;
        });
        $('#hoa').html(opt);
    });
}
$('#finYear').change(function(){
    let fy=$(this).val();
    fy ? loadHOA(fy) : $('#hoa').html('<option>-- Select HOA --</option>');
});
$(document).ready(function(){
    let fy=$('#finYear').val();
    if(fy) loadHOA(fy);
});

/* ================= LOAD PO ================= */
function loadPO(){
    $.getJSON('get_po_list.php', function(res){
        let opt='<option value="">-- Select PO --</option>';
        $.each(res,function(i,row){
            opt+=`<option value="${row.Id}">${row.POOrderNo} | Amount ₹${parseFloat(row.balance).toFixed(2)}</option>`;
        });
        $('#po_id').html(opt);
    });
}
$(document).ready(loadPO);

const GST_LIMIT_AMOUNT = 225000;
const GST_FIXED_PERCENT = 2;
/* ================= AMOUNT ENTRY================= */
$('#amount').on('input', function () {

    let amt = parseFloat(this.value) || 0;

    if (amt > selectedSanctionBalance) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
 /* 🔒 GST rule */
    if (amt > GST_LIMIT_AMOUNT) {
        $('#gstp')
            .val(GST_FIXED_PERCENT)
            .prop('readonly', true)
            .addClass('bg-light');
    } else {
        $('#gstp')
            .prop('readonly', false)
            .removeClass('bg-light');
    }
    $('#gst_span').text('GST is fixed at 2% for amounts above ₹2,25,000');        
    calcInvoice();           
});
$('#gstp').on('input', function () {

    let amt = parseFloat($('#amount').val()) || 0;

    if (amt > GST_LIMIT_AMOUNT) {
        this.value = GST_FIXED_PERCENT;
    }

    calcInvoice();
});
/* ================= PO CHANGE ================= */
$('#po_id').change(function () {

    let poId = $(this).val();

    $('#sanction_id').html('<option>Loading...</option>');
    $('#sanction_balance').val('0.00');
    $('#sanction_available_balance').val('0.00');

    selectedSanctionBalance = 0;
    poAvailableBalance = 0;

    if (!poId) return;

    /* ---- PO SUMMARY ---- */
    $.getJSON('get_po_sanction_summary.php', { POId: poId }, function (res) {

        poAvailableBalance = parseFloat(res.available_balance) || 0;

        $('#po_total_sanction').val(parseFloat(res.total_sanction).toFixed(2));
        $('#po_billed_amount').val(parseFloat(res.billed_amount).toFixed(2));
      //  $('#po_available_balance').val(poAvailableBalance.toFixed(2));

        updateAvailableSanctionBalance();
    });

    /* ---- SANCTION LIST ---- */
    $.getJSON('get_sanction_by_po.php', { POId: poId }, function (res) {

        let opt = '<option value="">-- Select Sanction --</option>';

        $.each(res, function (i, row) {
            opt += `
                <option value="${row.Id}" data-balance="${row.balance}">
                    ${row.SanctionOrderNo} | Amount ₹${parseFloat(row.balance).toFixed(2)}
                </option>`;
        });

        $('#sanction_id').html(opt);
    });
});


/* ================= SANCTION CHANGE MULTI================= */
// $('#sanction_id').on('change', function () {

//     let totalBalance = 0;

//     $('#sanction_id option:selected').each(function () {
//         let bal = parseFloat($(this).data('balance')) || 0;
//         totalBalance += bal;
//     });

//     $('#sanction_balance').val(totalBalance.toFixed(2));
// });


/* ================= SANCTION CHANGE ================= */
$('#sanction_id').change(function () {

    selectedSanctionBalance = parseFloat(
        $('option:selected', this).data('balance')
    ) || 0;

    $('#sanction_balance').val(selectedSanctionBalance.toFixed(2));

    updateAvailableSanctionBalance();
});



/* ================= CORE CALCULATION ================= */
function updateAvailableSanctionBalance() {

    let available = poAvailableBalance - selectedSanctionBalance;

    $('#sanction_available_balance').val(available.toFixed(2));

    if (available < 0) {
        $('#sanction_available_balance')
            .removeClass('text-success')
            .addClass('text-danger');
    } else {
        $('#sanction_available_balance')
            .removeClass('text-danger')
            .addClass('text-success');
    }
}

/* ================= CALCULATIONS ================= */
function percentCalc(base,p){return (base*p/100)||0;}

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
$('input').on('input',calcInvoice);

/* ================= VALIDATE TDS IT ================= */
$('#tds_it_p').blur(function(){
    let v=+this.value;
    if(v!==0 && v!==2 && v!==10){
        Swal.fire('Invalid','Only 2% or 10% allowed','warning');
        this.value='';
        this.focus();
    }
});

/* ================= FORM SUBMIT ================= */
$('#invoiceForm').submit(function(e){
    e.preventDefault();
if (isInvoiceDuplicate) {
        Swal.fire(
            'Error',
            'Please enter a unique Invoice Number',
            'error'
        );
        $('#invoice_no').focus();
        return false;
    }
    let invoiceAmt=+$('#amount').val()||0;
    let bal=+$('#sanction_balance').val()||0;


    if (invoiceAmt > selectedSanctionBalance) {
        Swal.fire(
            'Invalid Amount',
            'Invoice amount exceeds selected sanction balance',
            'error'
        );
        $('#amount').focus();
        return;
    }

    if(invoiceAmt>bal){
        Swal.fire('Invalid Amount','Invoice exceeds available sanction','error');
        return;
    }

    $.post('invoice_submit.php',$(this).serialize(),function(r){
        if(r.status==='success'){
            Swal.fire('Saved','Invoice created','success')
            .then(()=>location.href='invoice_list.php');
        }else{
            Swal.fire('Error',r.message,'error');
        }
    },'json');
});
</script>

</body>
</html>

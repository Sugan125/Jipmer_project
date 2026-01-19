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
    #sanction_id {
    min-height: 160px;
    padding: 8px;
    border-radius: 8px;
    background: #f8fbff;
    border: 1px solid #cfe2ff;
}

#sanction_id option {
    padding: 6px;
    margin-bottom: 3px;
}

#sanction_id option:checked {
    background: #0d6efd linear-gradient(0deg, #0d6efd 0%, #0d6efd 100%);
    color: #fff;
}
#sanction_gst_it_table table {
    font-size: 14px;
}
#sanction_gst_it_table th, #sanction_gst_it_table td {
    text-align: center;
    vertical-align: middle;
}
.po-details {
    display: none;
    background: #f8fbff;
    border: 1px solid #d6e4ff;
    border-radius: 10px;
    padding: 15px 20px;
}

.po-item {
    background: #ffffff;
    border: 1px solid #e3eafc;
    border-radius: 8px;
    padding: 10px 12px;
}

.po-label {
    display: block;
    font-size: 12px;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
}

.po-value {
    display: block;
    font-size: 15px;
    color: #212529;
    margin-top: 2px;
}
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

<!-- Section: PO & Sanction Mapping -->
<div class="section-card">
    <div class="section-title">PO & Sanction Mapping</div>

    <div class="row g-3">

        <!-- PO Selection -->
        <div class="col-md-4">
            <label>Purchase Order</label>
            <select name="POId" id="po_id" class="form-select" required>
                <option value="">-- Select PO --</option>
            </select>
        </div>

        <!-- PO Details Card -->
        <div id="po_details_box" class="col-12 po-details mt-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="po-item">
                        <span class="po-label">PO Number</span>
                        <span id="po_no" class="po-value"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="po-item">
                        <span class="po-label">PO Date</span>
                        <span id="po_date" class="po-value"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="po-item">
                        <span class="po-label">PO Amount</span>
                        <span id="po_amount" class="po-value text-primary fw-bold"></span>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="po-item">
                        <span class="po-label">PO GST %</span>
                        <span id="po_gst" class="po-value"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="po-item">
                        <span class="po-label">PO IT %</span>
                        <span id="po_it" class="po-value"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="po-item">
                        <span class="po-label">PO Net Amount</span>
                        <span id="po_net" class="po-value text-success fw-bold"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sanction Selection -->
        <div class="col-md-4 mt-3 mt-md-0">
            <label>Sanction Order</label>
            <select name="SanctionId[]" id="sanction_id" class="form-select" multiple required></select>
            <small class="text-primary fw-semibold">Select one or more sanctions</small>
        </div>
    </div>

    <!-- GST/IT Table -->
    <div id="sanction_gst_it_table" class="mt-3" style="display:none;">
        <div class="table-responsive">
            <table class="table table-bordered table-sm text-center">
                <thead class="table-light">
                    <tr>
                        <th>Sanction Order No</th>
                        <th>Sanction Amount</th>
<th>Used Amount</th>
<th>Balance Amount</th>
<th>GST %</th>
<th>IT %</th>
<th>Net Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- rows will be injected dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Totals Row -->
    <div class="row g-3 mt-3">
        <div class="col-md-3">
            <label>Total Sanction Amount</label>
            <input id="po_total_sanction" class="form-control fw-bold text-primary" readonly>
        </div>

        <div class="col-md-3">
            <label>Already Billed Amount</label>
            <input id="po_billed_amount" class="form-control fw-bold text-danger" readonly>
        </div>

        <div class="col-md-3">
            <label>Available Sanction Balance</label>
            <input id="sanction_available_balance" class="form-control fw-bold text-success" readonly>
        </div>
    </div>
</div>
<!-- Section 3: Amounts & Calculations -->
<div class="section-card">
<div class="section-title">Invoice Details</div>
<div class="row g-3">
<div class="col-md-3"><label>Invoice No</label><input name="InvoiceNo" id="invoice_no" class="form-control" required></div>
<div class="col-md-3"><label>Invoice Date</label><input type="date" name="InvoiceDate" class="form-control" required></div>

<div class="col-md-3">
<label>Invoice Amount</label>
<input type="number" step="0.01" id="amount" name="Amount" class="form-control">
<div class="invalid-feedback">
    Invoice amount cannot exceed selected sanction balance.
</div>
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
const TDS_THRESHOLD = 250000;
const TDS_GST_FIXED = 2;
const TDS_IT_THRESHOLD = 30000;
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
        let opt = '<option value="">-- Select PO --</option>';

        $.each(res, function(i, row){
            opt += `
                <option 
                    value="${row.Id}"
                    data-pono="${row.POOrderNo}"
                    data-podate="${row.POOrderDate}"
                    data-poamount="${row.POAmount}"
                    data-pogst="${row.POGSTPercent}"
                    data-poit="${row.POITPercent}"
                    data-ponet="${row.PONetAmount}"
                >
                    ${row.POOrderNo}
                </option>`;
        });

        $('#po_id').html(opt);
    });
}
$(document).ready(loadPO);

/* ================= AMOUNT ENTRY================= */
$('#amount').on('input', function () {

    let amt = parseFloat(this.value) || 0;

    if (amt > selectedSanctionBalance) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }

    calcInvoice();
});

/* ================= PO CHANGE ================= */
$('#po_id').change(function () {

    let poId = $(this).val();
if (!poId) {
    $('#po_details_box').slideUp();
    return;
}

let poAmount = parseFloat($('#po_id option:selected').data('poamount')) || 0;

if (poAmount > 250000) {

    // 🔒 Fix TDS GST %
    $('#tds_gst_p')
        .val(2)
        .prop('readonly', true)
        .addClass('bg-light');

} else {

    // 🔓 Allow edit
    $('#tds_gst_p')
        .prop('readonly', false)
        .removeClass('bg-light');
}

if (poAmount > 30000) {
    $('#tds_it_p')
        .prop('required', true)
        .addClass('border-danger');
} else {
    $('#tds_it_p')
        .prop('required', false)
        .removeClass('border-danger')
        .val('');
}

$('#tds_it_p').blur(function () {
    let v = +this.value;

    if (v !== 0 && v !== 2 && v !== 10) {
        Swal.fire('Invalid', 'Only 2% or 10% allowed', 'warning');
        this.value = '';
        $(this).addClass('border-danger');
        this.focus();
    } else if (v === 2 || v === 10) {
        $(this).removeClass('border-danger');
    }
});

/* Show PO details section */
$('#po_details_box').slideDown();
    let sel = $('#po_id option:selected');

$('#po_no').text(sel.data('pono') || '-');
$('#po_date').text(sel.data('podate') || '-');
$('#po_amount').text(
    sel.data('poamount') ? '₹ ' + parseFloat(sel.data('poamount')).toFixed(2) : '-'
);
$('#po_gst').text(sel.data('pogst') ? sel.data('pogst') + ' %' : '-');
$('#po_it').text(sel.data('poit') ? sel.data('poit') + ' %' : '-');
$('#po_net').text(
    sel.data('ponet') ? '₹ ' + parseFloat(sel.data('ponet')).toFixed(2) : '-'
);

    $('#sanction_id').html('<option>Loading...</option>');
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
                <option 
                    value="${row.Id}"
                    data-balance="${row.balance}"
                    data-no="${row.SanctionOrderNo}"
                    data-date="${row.SanctionDate}"
                    data-amount="${row.SanctionAmount}"
                    data-gst="${row.GSTPercent}"
                    data-it="${row.ITPercent}"
                    data-net="${row.SanctionNetAmount}"
                >
                    ${row.SanctionOrderNo}
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
$('#tds_gst_p').on('input', function () {
    let poAmount = parseFloat($('#po_id option:selected').data('poamount')) || 0;

    if (poAmount > 250000) {
        this.value = 2;
    }
});

/* ================= SANCTION CHANGE ================= */
$('#sanction_id').on('change', function () {

    let totalBalance = 0;
    let totalUsed = 0;
    
    let totalSanction = 0;
    let totalNet = 0;

    let tableRows = '';

    $('#sanction_id option:selected').each(function () {

        let sanctionAmt = parseFloat($(this).data('amount')) || 0;
        let balanceAmt  = parseFloat($(this).data('balance')) || 0;

        let usedAmt = sanctionAmt - balanceAmt;
        if (usedAmt < 0) usedAmt = 0;

        let gst = parseFloat($(this).data('gst')) || 0;
        let it  = parseFloat($(this).data('it')) || 0;
        let net = parseFloat($(this).data('net')) || 0;

        totalSanction += sanctionAmt;
        totalUsed += usedAmt;
        totalBalance += balanceAmt;
        totalNet += net;

        tableRows += `
            <tr>
                <td>${$(this).data('no')}</td>
                <td>₹ ${sanctionAmt.toFixed(2)}</td>
                <td class="text-danger">₹ ${usedAmt.toFixed(2)}</td>
                <td class="text-success">₹ ${balanceAmt.toFixed(2)}</td>
                <td>${gst.toFixed(2)} %</td>
                <td>${it.toFixed(2)} %</td>
                <td>₹ ${net.toFixed(2)}</td>
            </tr>
        `;
    });

    selectedSanctionBalance = totalBalance;

    if (totalSanction > 0) {
        $('#sanction_gst_it_table').slideDown();
    } else {
        $('#sanction_gst_it_table').slideUp();
    }

    // Inject rows
    $('#sanction_gst_it_table tbody').html(tableRows);

    // Footer totals
    let footer = `
        <tr class="table-secondary fw-bold">
            <td>Total</td>
            <td>₹ ${totalSanction.toFixed(2)}</td>
            <td class="text-danger">₹ ${totalUsed.toFixed(2)}</td>
            <td class="text-success">₹ ${totalBalance.toFixed(2)}</td>
            <td></td>
            <td></td>
            <td>₹ ${totalNet.toFixed(2)}</td>
        </tr>
    `;

    $('#sanction_gst_it_table tbody').append(footer);

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

function calcInvoice() {

    let amount = +$('#amount').val() || 0;

    let tdsG = percentCalc(amount, +$('#tds_gst_p').val());
    let tdsI = percentCalc(amount, +$('#tds_it_p').val());

    let tdsTotal = tdsG + tdsI;

    $('#tds_gst_amt').text('TDS GST: ' + tdsG.toFixed(2));
    $('#tds_it_amt').text('TDS IT: ' + tdsI.toFixed(2));

    $('#invoice_total').val(amount.toFixed(2));
    $('#tds_total').val(tdsTotal.toFixed(2));
    $('#net_payable').val((amount - tdsTotal).toFixed(2));
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

    let poAmount = parseFloat($('#po_id option:selected').data('poamount')) || 0;
let tdsIT = $('#tds_it_p').val();

if (poAmount > 30000 && !tdsIT) {
    Swal.fire(
        'TDS IT Required',
        'TDS IT % is mandatory when PO amount exceeds ₹30,000',
        'warning'
    );
    $('#tds_it_p').focus();
    return false;
}


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

    if (invoiceAmt > selectedSanctionBalance) {
        Swal.fire(
            'Invalid Amount',
            'Invoice amount exceeds selected sanction balance',
            'error'
        );
        $('#amount').focus();
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

<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>PO & Sanction Order Entry</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1100px;margin:auto;}
.section-card{border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:25px;background:#f9f9f9;}
.section-title{font-weight:600;color:#0d6efd;margin-bottom:15px;}
small{color:#198754;font-weight:600;}
.table td,.table th{vertical-align:middle;}
.balance{font-weight:700;color:#dc3545;}
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">

<h4 class="text-primary mb-3">
<i class="fa fa-file-contract"></i> PO & Sanction Order Entry
</h4>

<form id="poForm">

<!-- ================= PO DETAILS ================= -->
<div class="section-card">
<div class="section-title">Purchase Order (PO) Details</div>

<div class="row g-3">
    <div class="col-md-3">
        <label>PO Number</label>
        <input type="text" name="PONumber" class="form-control" required>
    </div>

    <div class="col-md-3">
        <label>PO Date</label>
        <input type="date" name="PODate" class="form-control" required>
    </div>

    <div class="col-md-3">
        <label>PO Amount</label>
        <input type="number" step="0.01" id="po_amount" name="POAmount" class="form-control" required>
    </div>

    <div class="col-md-3">
        <label>PO GST %</label>
        <input type="number" step="0.01" max="100" id="po_gst_p" name="POGSTPercent" class="form-control">
        <small id="po_gst_amt"></small>
    </div>

    <div class="col-md-3">
        <label>PO IT %</label>
        <input type="number" step="0.01" max="100" id="po_it_p" name="POITPercent" class="form-control">
        <small id="po_it_amt"></small>
    </div>

    <div class="col-md-3">
        <label>PO Net Total</label>
        <input readonly id="po_net_total" class="form-control bg-light fw-bold">
    </div>

    <div class="col-md-3">
        <label>Remaining Balance</label>
        <input readonly id="remaining_balance" class="form-control bg-light balance">
    </div>
</div>
</div>

<!-- ================= SANCTION SECTION ================= -->
<div class="section-card">
<div class="section-title d-flex justify-content-between">
    <span>Sanction Orders</span>
    <button type="button" class="btn btn-sm btn-primary" id="addRow">
        <i class="fa fa-plus"></i> Add Sanction
    </button>
</div>

<div class="table-responsive">
<table class="table table-bordered" id="sanctionTable">
<thead class="table-light">
<tr>
    <th>Sanction No</th>
    <th>Date</th>
    <th>Amount</th>
    <th>GST %</th>
    <th>GST Amt</th>
    <th>IT %</th>
    <th>IT Amt</th>
    <th>Net</th>
    <th>Action</th>
</tr>
</thead>
<tbody></tbody>
</table>
</div>
</div>

<div class="text-end">
    <button type="submit" class="btn btn-success">
        <i class="fa fa-save"></i> Save PO & Sanctions
    </button>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
function percentCalc(base,p){ return (base*p/100)||0; }

/* ================= PO CALC ================= */
function calcPO(){
 let a=+$('#po_amount').val()||0;
 let gst=percentCalc(a,+$('#po_gst_p').val());
 let it=percentCalc(a,+$('#po_it_p').val());

 $('#po_gst_amt').text('GST : '+gst.toFixed(2));
 $('#po_it_amt').text('IT : '+it.toFixed(2));
 $('#po_net_total').val((a+gst+it).toFixed(2));

 $('.sgstp').val($('#po_gst_p').val());
 $('.sitp').val($('#po_it_p').val());

 calcSanction();
}

/* ================= SANCTION ================= */
function addRow(){
 let row = `
 <tr>
  <td><input name="SanctionNo[]" class="form-control" required></td>
  <td><input type="date" name="SanctionDate[]" class="form-control" required></td>
  <td><input type="number" step="0.01" name="SanctionAmount[]" class="form-control samt"></td>
  <td><input readonly class="form-control sgstp"></td>
  <td class="sgsta text-success fw-bold"></td>
  <td><input readonly class="form-control sitp"></td>
  <td class="sita text-success fw-bold"></td>
  <td class="snet fw-bold"></td>
  <td><button type="button" class="btn btn-sm btn-danger remove">X</button></td>
 </tr>`;
 $('#sanctionTable tbody').append(row);
 calcPO();
}

function calcSanction(){
 let total=0;

 $('#sanctionTable tbody tr').each(function(){
  let amt = +$(this).find('.samt').val()||0;
  let gp  = +$('#po_gst_p').val()||0;
  let ip  = +$('#po_it_p').val()||0;

  let gst = percentCalc(amt,gp);
  let it  = percentCalc(amt,ip);

  $(this).find('.sgsta').text(gst.toFixed(2));
  $(this).find('.sita').text(it.toFixed(2));
  $(this).find('.snet').text((amt+gst+it).toFixed(2));

  total += amt;
 });

 let po = +$('#po_amount').val()||0;
 let bal = po - total;

 $('#remaining_balance').val(bal.toFixed(2));

 if(total > po){
     Swal.fire('Error','Total Sanction Amount exceeds PO Amount','error');
     $('button[type="submit"]').prop('disabled',true);
 } else {
     $('button[type="submit"]').prop('disabled',false);
 }
}

/* ================= EVENTS ================= */
$('#po_amount,#po_gst_p,#po_it_p').on('input',calcPO);
$('#addRow').click(addRow);
$(document).on('input','.samt',calcSanction);
$(document).on('click','.remove',function(){
    $(this).closest('tr').remove();
    calcSanction();
});

/* ================= SUBMIT ================= */
$('#poForm').submit(function(e){
 e.preventDefault();

 let po = +$('#po_amount').val()||0;
 let total=0;
 $('.samt').each(function(){ total+=+$(this).val()||0; });

 if(total > po){
    Swal.fire('Error','Total Sanction Amount exceeds PO Amount','error');
    return;
 }

 $.ajax({
    url:'po_sanction_submit.php',
    type:'POST',
    data:$(this).serialize(),
    dataType:'json',
    success:function(res){
       if(res.status === 'success'){
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'PO & Sanction Orders have been saved successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload(); // reload page after clicking OK
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message,
                    confirmButtonText: 'OK'
                });
            }
    },
        error: function(xhr, status, error){
            Swal.fire({
                icon: 'error',
                title: 'AJAX Error',
                text: error,
                confirmButtonText: 'OK'
            });
        }
 });
});

addRow();
</script>

</body>
</html>

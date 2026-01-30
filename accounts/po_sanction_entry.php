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
          <label>GST Number</label>
          <input type="text" name="GSTNumber" class="form-control" required>
      </div>

      <div class="col-md-3">
          <label>PO Total Amount</label>
          <input readonly id="po_amount" name="POAmount" class="form-control bg-light fw-bold" value="0.00">
          <small class="text-muted">Calculated from items</small>
      </div>

      <div class="col-md-3">
          <label>PO Net Total</label>
          <input readonly id="po_net_total" class="form-control bg-light fw-bold" value="0.00">
      </div>

      <div class="col-md-3">
        
      </div>
  </div>
</div>

<!-- ================= PO ITEMS ================= -->
<div class="section-card">
  <div class="section-title d-flex justify-content-between">
      <span>PO Items (Item-wise)</span>
      <button type="button" class="btn btn-sm btn-primary" id="addItemRow">
          <i class="fa fa-plus"></i> Add Item
      </button>
  </div>

  <div class="table-responsive">
      <table class="table table-bordered" id="poItemTable">
          <thead class="table-light">
              <tr>
                <th>Item Name</th>
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
          <tfoot class="table-light fw-bold">
              <tr>
                <td class="text-end">TOTAL</td>
                <td id="po_total_base">0.00</td>
                <td></td>
                <td id="po_total_gst">0.00</td>
                <td></td>
                <td id="po_total_it">0.00</td>
                <td id="po_total_net">0.00</td>
                <td></td>
              </tr>
          </tfoot>
      </table>
  </div>
</div>

<!-- ================= BANK DETAILS ================= -->
<div class="section-card">
  <div class="section-title">Bank & Account Details</div>

  <div class="row g-3">
      <div class="col-md-3">
          <label>PAN Number</label>
          <input name="PanNumber" class="form-control">
      </div>

      <div class="col-md-3">
          <label>PFMS Unique Number</label>
          <input name="PFMSNumber" class="form-control">
      </div>

      <div class="col-md-3">
          <label>Bank Name</label>
          <input name="BankName" class="form-control">
      </div>

      <div class="col-md-3">
          <label>IFSC</label>
          <input name="IFSC" class="form-control">
      </div>

      <div class="col-md-3">
          <label>Account Number</label>
          <input name="AccountNumber" class="form-control">
      </div>
  </div>
</div>

<!-- ================= SANCTION SECTION ================= -->
<div class="section-card">
  <div class="section-title d-flex justify-content-between align-items-center">
      <span>Sanction Orders</span>
      <button type="button" class="btn btn-sm btn-primary" id="addSanRow">
          <i class="fa fa-plus"></i> Add Sanction
      </button>
  </div>

  <!-- ✅ Remaining Balance display in sanction section -->
  <div class="row mb-2">
    <div class="col-md-12 text-end">
      <span class="fw-bold text-secondary me-2">Remaining Balance:</span>
      <span id="san_remaining_balance" class="fw-bold text-danger">0.00</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered" id="sanctionTable">
      <thead class="table-light">
        <tr>
          <th>Sanction No</th>
          <th>Date</th>
          <th>Amount</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody></tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td colspan="2" class="text-end">TOTAL</td>
          <td id="san_total_amt">0.00</td>
          <td></td>
        </tr>
      </tfoot>
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

/* ================= PO ITEMS ================= */
function addItemRow(){
  let row = `
  <tr>
    <td><input name="ItemName[]" class="form-control" required></td>
    <td><input type="number" step="0.01" name="ItemAmount[]" class="form-control iamt" value="0"></td>
    <td><input type="number" step="0.01" max="100" name="ItemGSTPercent[]" class="form-control igstp" value="0"></td>
    <td class="igsta text-success fw-bold">0.00</td>
    <td><input type="number" step="0.01" max="100" name="ItemITPercent[]" class="form-control iitp" value="0"></td>
    <td class="iita text-success fw-bold">0.00</td>
    <td class="inet fw-bold">0.00</td>
    <td><button type="button" class="btn btn-sm btn-danger removeItem">X</button></td>
  </tr>`;
  $('#poItemTable tbody').append(row);
  calcPOItems();
}

function calcPOItems(){
  let totalBase = 0, totalGST = 0, totalIT = 0, totalNet = 0;

  $('#poItemTable tbody tr').each(function(){
    let amt = parseFloat($(this).find('input.iamt').val()) || 0;
    let gp  = parseFloat($(this).find('input.igstp').val()) || 0;
    let ip  = parseFloat($(this).find('input.iitp').val()) || 0;

    let gst = percentCalc(amt, gp);
    let it  = percentCalc(amt, ip);
    let net = amt + gst + it;

    $(this).find('td.igsta').text(gst.toFixed(2));
    $(this).find('td.iita').text(it.toFixed(2));
    $(this).find('td.inet').text(net.toFixed(2));

    totalBase += amt;
    totalGST  += gst;
    totalIT   += it;
    totalNet  += net;
  });

  $('#po_total_base').text(totalBase.toFixed(2));
  $('#po_total_gst').text(totalGST.toFixed(2));
  $('#po_total_it').text(totalIT.toFixed(2));
  $('#po_total_net').text(totalNet.toFixed(2));

  $('#po_amount').val(totalBase.toFixed(2));
  $('#po_net_total').val(totalNet.toFixed(2));

  calcSanction();
}

/* ================= SANCTION ================= */
function addSanRow(){
  let row = `
  <tr>
    <td><input name="SanctionNo[]" class="form-control" required></td>
    <td><input type="date" name="SanctionDate[]" class="form-control" required></td>
    <td><input type="number" step="0.01" name="SanctionAmount[]" class="form-control samt" value="0"></td>
    <td><button type="button" class="btn btn-sm btn-danger removeSan">X</button></td>
  </tr>`;
  $('#sanctionTable tbody').append(row);
  calcSanction();
}

function calcSanction(){
  let totalSanctionNet = 0;

  $('#sanctionTable tbody tr').each(function(){
    let amt = parseFloat($(this).find('input.samt').val()) || 0;
    totalSanctionNet += amt; // since sanction net = amount (no GST/IT in sanction)
  });

  $('#san_total_amt').text(totalSanctionNet.toFixed(2));

  // ✅ Compare with PO NET total (calculated from itemwise net)
  let poNet = parseFloat($('#po_net_total').val()) || 0;
  let bal = poNet - totalSanctionNet;

  $('#remaining_balance').val(bal.toFixed(2));
$('#san_remaining_balance').text(bal.toFixed(2));
  if(totalSanctionNet > poNet){
    Swal.fire('Error','Total Sanction Amount exceeds PO Net Total (Item-wise Net)','error');
    $('button[type="submit"]').prop('disabled', true);
  } else {
    $('button[type="submit"]').prop('disabled', false);
  }
}


/* ================= EVENTS ================= */
$('#addItemRow').on('click', addItemRow);

// delegated events for dynamic rows
$(document).on('input', '#poItemTable .iamt, #poItemTable .igstp, #poItemTable .iitp', calcPOItems);
$(document).on('click', '.removeItem', function(){
  $(this).closest('tr').remove();
  calcPOItems();
});

$('#addSanRow').on('click', addSanRow);
$(document).on('input', '#sanctionTable .samt', calcSanction);
$(document).on('click', '.removeSan', function(){
  $(this).closest('tr').remove();
  calcSanction();
});

// Check PO Number duplicate
$('input[name="PONumber"]').on('blur', function(){
  let poNum = $(this).val().trim();
  if(poNum==='') return;

  $.get('check_duplicate.php', { type:'po', value: poNum }, function(res){
    if(res.duplicate){
      Swal.fire('Duplicate','PO Number already exists!','warning');
      $('input[name="PONumber"]').val('').focus();
    }
  },'json');
});

// Check Sanction Number duplicate
$(document).on('blur','input[name="SanctionNo[]"]', function(){
  let sanNo = $(this).val().trim();
  if(sanNo==='') return;

  $.get('check_duplicate.php', { type:'sanction', value: sanNo }, function(res){
    if(res.duplicate){
      Swal.fire('Duplicate','Sanction Number already exists!','warning');
      $(this).val('').focus();
    }
  }.bind(this),'json');
});

/* ================= SUBMIT ================= */
$('#poForm').submit(function(e){
  e.preventDefault();

  let poNet = parseFloat($('#po_net_total').val()) || 0;

  if(poNet <= 0){
    Swal.fire('Error','Please add at least one PO item','error');
    return;
  }

  // ✅ calculate total sanction amount
  let totalSan = 0;
  $('#sanctionTable .samt').each(function(){
    totalSan += parseFloat($(this).val()) || 0;
  });

  // ✅ compare against NET
  if(totalSan > poNet){
    Swal.fire(
      'Error',
      'Total Sanction Amount exceeds PO Net Total (Item-wise Net)',
      'error'
    );
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
          text: 'PO Items & Sanction Orders have been saved successfully.',
          confirmButtonText: 'OK'
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: res.message,
          confirmButtonText: 'OK'
        });
      }
    },
    error:function(xhr, status, error){
      Swal.fire({
        icon: 'error',
        title: 'AJAX Error',
        text: error,
        confirmButtonText: 'OK'
      });
    }
  });
});

// defaults
addItemRow();
addSanRow();
</script>

</body>
</html>

<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

$poId = (int)($_SESSION['po_context_id'] ?? 0);
if($poId <= 0) die('PO ID Missing in session. Go back to list.');

/* ===== FETCH PO ===== */
$poStmt = $conn->prepare("SELECT * FROM po_master WHERE Id = ?");
$poStmt->execute([$poId]);
$po = $poStmt->fetch(PDO::FETCH_ASSOC);
if(!$po) die('Invalid PO');

/* ===== BANK (latest by Id) ===== */
$bankStmt = $conn->prepare("SELECT TOP 1 * FROM po_bank_details WHERE po_id = ? ORDER BY Id DESC");
$bankStmt->execute([$poId]);
$bank = $bankStmt->fetch(PDO::FETCH_ASSOC);

/* ===== ITEMS ===== */
$itemStmt = $conn->prepare("SELECT * FROM po_items WHERE POId = ? ORDER BY Id ASC");
$itemStmt->execute([$poId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== SANCTIONS ===== */
$sanStmt = $conn->prepare("SELECT * FROM sanction_order_master WHERE POId = ? ORDER BY Id ASC");
$sanStmt->execute([$poId]);
$sanctions = $sanStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit PO & Sanction Orders</title>

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

<h4 class="text-primary mb-3"><i class="fa fa-edit"></i> Edit PO & Sanction Orders</h4>

<form id="poForm">
<input type="hidden" name="POId" value="<?= $poId ?>">

<!-- ================= PO DETAILS ================= -->
<div class="section-card">
  <div class="section-title">Purchase Order (PO) Details</div>

  <div class="row g-3">
      <div class="col-md-3">
          <label>PO Number</label>
          <input type="text" name="PONumber" class="form-control" required value="<?= htmlspecialchars($po['POOrderNo']) ?>">
      </div>

      <div class="col-md-3">
          <label>PO Date</label>
          <input type="date" name="PODate" class="form-control" required value="<?= htmlspecialchars(substr($po['POOrderDate'],0,10)) ?>">
      </div>

      <div class="col-md-3">
          <label>GST Number</label>
          <input type="text" name="GSTNumber" class="form-control" required value="<?= htmlspecialchars($po['GSTNumber'] ?? '') ?>">
      </div>

      <div class="col-md-3">
          <label>PO Total Amount</label>
          <input readonly id="po_amount" name="POAmount" class="form-control bg-light fw-bold" value="<?= number_format((float)$po['POAmount'],2,'.','') ?>">
          <small class="text-muted">Calculated from items</small>
      </div>

      <div class="col-md-3">
          <label>PO Net Total</label>
          <input readonly id="po_net_total" class="form-control bg-light fw-bold" value="<?= number_format((float)$po['PONetAmount'],2,'.','') ?>">
      </div>

      <div class="col-md-3">
          <label>Remaining Balance</label>
          <input readonly id="remaining_balance" class="form-control bg-light balance" value="0.00">
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
          <tbody>
          <?php if(!empty($items)): ?>
            <?php foreach($items as $it): ?>
              <tr>
                <td>
                  <input type="hidden" name="ItemId[]" value="<?= (int)$it['Id'] ?>">
                  <input name="ItemName[]" class="form-control" required value="<?= htmlspecialchars($it['ItemName']) ?>">
                </td>
                <td><input type="number" step="0.01" name="ItemAmount[]" class="form-control iamt" value="<?= number_format((float)$it['ItemAmount'],2,'.','') ?>"></td>
                <td><input type="number" step="0.01" max="100" name="ItemGSTPercent[]" class="form-control igstp" value="<?= number_format((float)$it['GSTPercent'],2,'.','') ?>"></td>
                <td class="igsta text-success fw-bold"><?= number_format((float)$it['GSTAmount'],2) ?></td>
                <td><input type="number" step="0.01" max="100" name="ItemITPercent[]" class="form-control iitp" value="<?= number_format((float)$it['ITPercent'],2,'.','') ?>"></td>
                <td class="iita text-success fw-bold"><?= number_format((float)$it['ITAmount'],2) ?></td>
                <td class="inet fw-bold"><?= number_format((float)$it['NetAmount'],2) ?></td>
                <td><button type="button" class="btn btn-sm btn-danger removeItem">X</button></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
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
          <input name="PanNumber" class="form-control" value="<?= htmlspecialchars($bank['pan_number'] ?? '') ?>">
      </div>

      <div class="col-md-3">
          <label>PFMS Unique Number</label>
          <input name="PFMSNumber" class="form-control" value="<?= htmlspecialchars($bank['pfms_number'] ?? '') ?>">
      </div>

      <div class="col-md-3">
          <label>Bank Name</label>
          <input name="BankName" class="form-control" value="<?= htmlspecialchars($bank['bank_name'] ?? '') ?>">
      </div>

      <div class="col-md-3">
          <label>IFSC</label>
          <input name="IFSC" class="form-control" value="<?= htmlspecialchars($bank['ifsc'] ?? '') ?>">
      </div>

      <div class="col-md-3">
          <label>Account Number</label>
          <input name="AccountNumber" class="form-control" value="<?= htmlspecialchars($bank['account_number'] ?? '') ?>">
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
      <tbody>
      <?php if(!empty($sanctions)): ?>
      <?php foreach($sanctions as $s): ?>
        <tr>
          <td>
            <input type="hidden" name="SanctionId[]" value="<?= (int)$s['Id'] ?>">
            <input name="SanctionNo[]" class="form-control" required value="<?= htmlspecialchars($s['SanctionOrderNo']) ?>">
          </td>
          <td><input type="date" name="SanctionDate[]" class="form-control" required value="<?= htmlspecialchars(substr($s['SanctionDate'],0,10)) ?>"></td>
          <td><input type="number" step="0.01" name="SanctionAmount[]" class="form-control samt" value="<?= number_format((float)$s['SanctionAmount'],2,'.','') ?>"></td>
          <td><button type="button" class="btn btn-sm btn-danger removeSan">X</button></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
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
    <i class="fa fa-save"></i> Update
  </button>
  <a href="po_sanction_list.php" class="btn btn-secondary">Back</a>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
function percentCalc(base,p){ return (base*p/100)||0; }

/* ================= ITEMS ================= */
function addItemRow(){
  let row = `
  <tr>
    <td>
      <input type="hidden" name="ItemId[]" value="">
      <input name="ItemName[]" class="form-control" required>
    </td>
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
    let net = amt + gst + it; // (if IT is deduction change to: amt + gst - it)

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
    <td>
      <input type="hidden" name="SanctionId[]" value="">
      <input name="SanctionNo[]" class="form-control" required>
    </td>
    <td><input type="date" name="SanctionDate[]" class="form-control" required></td>
    <td><input type="number" step="0.01" name="SanctionAmount[]" class="form-control samt" value="0"></td>
    <td><button type="button" class="btn btn-sm btn-danger removeSan">X</button></td>
  </tr>`;
  $('#sanctionTable tbody').append(row);
  calcSanction();
}

function calcSanction(){
  let totalSanction = 0;

  $('#sanctionTable tbody tr').each(function(){
    let amt = parseFloat($(this).find('input.samt').val()) || 0;
    totalSanction += amt;
  });

  $('#san_total_amt').text(totalSanction.toFixed(2));

  let poNet = parseFloat($('#po_net_total').val()) || 0;
  let bal = poNet - totalSanction;

  $('#remaining_balance').val(bal.toFixed(2));
  $('#san_remaining_balance').text(bal.toFixed(2));

  if(totalSanction > poNet){
    Swal.fire('Error','Total Sanction Amount exceeds PO Net Total (Item-wise Net)','error');
    $('button[type="submit"]').prop('disabled', true);
  }else{
    $('button[type="submit"]').prop('disabled', false);
  }
}

/* ================= EVENTS ================= */
$('#addItemRow').on('click', addItemRow);
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

// init
calcPOItems();
calcSanction();
if($('#poItemTable tbody tr').length === 0) addItemRow();
if($('#sanctionTable tbody tr').length === 0) addSanRow();

/* ================= SUBMIT ================= */
$('#poForm').submit(function(e){
  e.preventDefault();

  let poNet = parseFloat($('#po_net_total').val()) || 0;
  if(poNet <= 0){
    Swal.fire('Error','Please add at least one valid PO item','error');
    return;
  }

  $.ajax({
    url:'po_sanction_update.php',
    type:'POST',
    data:$(this).serialize(),
    dataType:'json',
    success:function(res){
      if(res.status === 'success'){
        Swal.fire('Updated','PO updated successfully','success')
          .then(()=>location.href='po_sanction_list.php');
      }else{
        Swal.fire('Error',res.message,'error');
      }
    },
    error:function(){
      Swal.fire('Error','AJAX Error','error');
    }
  });
});
</script>

</body>
</html>

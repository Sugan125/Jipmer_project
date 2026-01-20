<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

if(!isset($_GET['po_id'])){
    die('PO ID Missing');
}

$poId = (int)$_GET['po_id'];

/* ===== FETCH PO ===== */
$poStmt = $conn->prepare("SELECT * FROM po_master WHERE Id = ?");
$poStmt->execute([$poId]);
$po = $poStmt->fetch();

if(!$po){
    die('Invalid PO');
}

/* ===== FETCH SANCTIONS ===== */
$sanStmt = $conn->prepare("SELECT * FROM sanction_order_master WHERE POId = ?");
$sanStmt->execute([$poId]);
$sanctions = $sanStmt->fetchAll();

$bankStmt = $conn->prepare("
    SELECT * FROM po_bank_details
    WHERE po_id = ? AND is_active = 1
");
$bankStmt->execute([$poId]);
$bank = $bankStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit PO & Sanction</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1100px;margin:auto;}
.section-card{border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:25px;background:#f9f9f9;}
.section-title{font-weight:600;color:#0d6efd;margin-bottom:15px;}
.table td,.table th{vertical-align:middle;}
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

<div class="section-card">
<div class="section-title">PO Details</div>

<div class="row g-3">
    <div class="col-md-3">
        <label>PO Number</label>
        <input name="PONumber" class="form-control" value="<?= $po['POOrderNo'] ?>" required>
    </div>
    <div class="col-md-3">
        <label>PO Date</label>
        <input type="date" name="PODate" class="form-control" value="<?= $po['POOrderDate'] ?>" required>
    </div>
    <div class="col-md-3">
        <label>PO Amount</label>
        <input type="number" step="0.01" id="po_amount" name="POAmount" class="form-control" value="<?= $po['POAmount'] ?>">
    </div>
    <div class="col-md-3">
        <label>PO Net Total</label>
        <input readonly id="po_net_total" class="form-control bg-light fw-bold" value="<?= $po['PONetAmount'] ?>">
    </div>
    <div class="col-md-3">
        <label>GST %</label>
        <input type="number" step="0.01" id="po_gst_p" name="POGSTPercent" class="form-control" value="<?= $po['POGSTPercent'] ?>">
        <small id="po_gst_amt"></small>
    </div>
    <div class="col-md-3">
        <label>IT %</label>
        <input type="number" step="0.01" id="po_it_p" name="POITPercent" class="form-control" value="<?= $po['POITPercent'] ?>">
        <small id="po_it_amt"></small>
    </div>
</div>
</div>
<div class="section-card">
    <div class="section-title">Bank & Account Details</div>

    <div class="row g-3">
        <div class="col-md-3">
            <label>PAN Number</label>
            <input name="PanNumber" class="form-control" value="<?= $bank['pan_number'] ?>">
        </div>

        <div class="col-md-3">
            <label>PFMS Unique Number</label>
            <input name="PFMSNumber" class="form-control" value="<?= $bank['pfms_number'] ?>">
        </div>

        <div class="col-md-3">
            <label>Bank Name</label>
            <input name="BankName" class="form-control" value="<?= $bank['bank_name'] ?>">
        </div>

        <div class="col-md-3">
            <label>IFSC</label>
            <input name="IFSC" class="form-control" value="<?= $bank['ifsc'] ?>">
        </div>

        <div class="col-md-3">
            <label>Account Number</label>
            <input name="AccountNumber" class="form-control" value="<?= $bank['account_number'] ?>">
        </div>
    </div>
</div>
<div class="section-card">
<div class="section-title d-flex justify-content-between">
    <span>Sanction Orders</span>
    <button type="button" class="btn btn-sm btn-primary" id="addRow"><i class="fa fa-plus"></i> Add</button>
</div>

<table class="table table-bordered" id="sanctionTable">
<thead class="table-light">
<tr>
<th>No</th><th>Date</th><th>Amount</th><th>GST%</th><th>GST</th><th>IT%</th><th>IT</th><th>Net</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($sanctions as $s): ?>
<tr>
<td>
    <input name="SanctionId[]" type="hidden" value="<?= $s['Id'] ?>">
    <input name="SanctionNo[]" class="form-control" value="<?= $s['SanctionOrderNo'] ?>">
</td>
<td><input type="date" name="SanctionDate[]" class="form-control" value="<?= $s['SanctionDate'] ?>"></td>
<td><input name="SanctionAmount[]" class="form-control samt" value="<?= $s['SanctionAmount'] ?>"></td>
<td><input readonly class="form-control sgstp" value="<?= $s['GSTPercent'] ?>"></td>
<td class="sgsta fw-bold"><?= number_format($s['GSTAmount'],2) ?></td>
<td><input readonly class="form-control sitp" value="<?= $s['ITPercent'] ?>"></td>
<td class="sita fw-bold"><?= number_format($s['ITAmount'],2) ?></td>
<td class="snet fw-bold"><?= number_format($s['SanctionNetAmount'],2) ?></td>
<td><button type="button" class="btn btn-danger btn-sm remove">X</button></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="table-light fw-bold">
<tr>
    <td colspan="4" class="text-end">TOTAL</td>
    <td id="total_gst">0.00</td>
    <td></td>
    <td id="total_it">0.00</td>
    <td id="total_net">0.00</td>
    <td></td>
</tr>
</tfoot>

</table>
</div>

<div class="text-end">
<button class="btn btn-success"><i class="fa fa-save"></i> Update</button>
<a href="po_sanction_list.php" class="btn btn-secondary">Back</a>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
function percentCalc(a,p){return (a*p/100)||0;}

function calcPO(){
 let a=+$('#po_amount').val()||0;
 let gp=+$('#po_gst_p').val()||0;
 let ip=+$('#po_it_p').val()||0;
 let g=percentCalc(a,gp), i=percentCalc(a,ip);
 $('#po_gst_amt').text('GST '+g.toFixed(2));
 $('#po_it_amt').text('IT '+i.toFixed(2));
 $('#po_net_total').val((a+g+i).toFixed(2));
 $('.sgstp').val(gp);
 $('.sitp').val(ip);
 calcSanction();
}

function calcSanction(){

    let totalGST = 0;
    let totalIT  = 0;
    let totalNet = 0;

    $('#sanctionTable tbody tr').each(function(){

        let amt = parseFloat($(this).find('.samt').val()) || 0;
        let gp  = parseFloat($('#po_gst_p').val()) || 0;
        let ip  = parseFloat($('#po_it_p').val()) || 0;

        let gst = percentCalc(amt, gp);
        let it  = percentCalc(amt, ip);
        let net = amt + gst + it;

        $(this).find('.sgsta').text(gst.toFixed(2));
        $(this).find('.sita').text(it.toFixed(2));
        $(this).find('.snet').text(net.toFixed(2));

        totalGST += gst;
        totalIT  += it;
        totalNet += net;
    });

    $('#total_gst').text(totalGST.toFixed(2));
    $('#total_it').text(totalIT.toFixed(2));
    $('#total_net').text(totalNet.toFixed(2));
}


$('#addRow').on('click', function () {

    let rowCount = $('#sanctionTable tbody tr').length + 1;

    let row = `
    <tr>
        <td>
            <input name="SanctionId[]" type="hidden" value="">
            <input name="SanctionNo[]" class="form-control" placeholder="Sanction No">
        </td>
        <td>
            <input type="date" name="SanctionDate[]" class="form-control">
        </td>
        <td>
            <input name="SanctionAmount[]" class="form-control samt" value="">
        </td>
        <td>
            <input readonly class="form-control sgstp" value="${$('#po_gst_p').val()}">
        </td>
        <td class="sgsta fw-bold">0.00</td>
        <td>
            <input readonly class="form-control sitp" value="${$('#po_it_p').val()}">
        </td>
        <td class="sita fw-bold">0.00</td>
        <td class="snet fw-bold">0.00</td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove">X</button>
        </td>
    </tr>
    `;

    $('#sanctionTable tbody').append(row);
});

$('#po_amount,#po_gst_p,#po_it_p').on('input',calcPO);
$(document).on('input','.samt',calcSanction);
$(document).on('click','.remove',function(){ $(this).closest('tr').remove(); });

calcPO();

$('#poForm').submit(function(e){
 e.preventDefault();
 $.post('po_sanction_update.php',$(this).serialize(),function(res){
    if(res.status==='success'){
        Swal.fire('Updated','PO Updated Successfully','success')
        .then(()=>location.href='po_sanction_list.php');
    }else{
        Swal.fire('Error',res.message,'error');
    }
 },'json');
});
</script>
</body>
</html>

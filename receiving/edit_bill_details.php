<?php
include '../config/db.php';
include '../includes/auth.php';

if (!isset($_GET['id'])) {
    die("Invalid Bill ID");
}

$billId = intval($_GET['id']);

/* ===== Fetch Bill Details ===== */
$stmt = $conn->prepare("SELECT * FROM bill_initial_entry WHERE Id=?");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) die("Bill not found");

/* ===== Fetch Bill Types ===== */
$billTypes = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1")->fetchAll(PDO::FETCH_ASSOC);

/* ===== Fetch all invoices ===== */
$invoices = $conn->query("
    SELECT i.*, bim.BillInitialId, bie.*
    FROM invoice_master i
    LEFT JOIN bill_invoice_map bim 
    left join bill_initial_entry bie on bie.Id = bim.BillInitialId
    ON i.Id = bim.InvoiceId AND bim.BillInitialId = $billId
    ORDER BY i.InvoiceNo
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Bill Details</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link href="../js/select2/select2.min.css" rel="stylesheet">
<script src="../js/select2/select2.min.js"></script>

<style>
.page-content{ margin-left:240px; padding:90px 30px 30px; }
.card{ max-width:900px; margin:auto; }
.invoice-box { max-height: 260px; overflow-y:auto; background:#fff; padding:10px; border:1px solid #ddd; border-radius:5px; }
.invoice-box::-webkit-scrollbar { width:6px; }
.invoice-box::-webkit-scrollbar-thumb { background:#bbb; border-radius:5px; }
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card shadow-sm p-4">
<h4 class="mb-4 text-primary"><i class="fa fa-file-invoice me-2"></i>Edit Bill Details</h4>

<form id="billDetailsForm">

<input type="hidden" name="BillId" value="<?= $billId ?>">

<div class="row g-3">

<!-- Bill Number -->
<div class="col-md-6">
<label class="form-label">Bill Number</label>
<input type="text" name="BillNumber" class="form-control" required value="<?= htmlspecialchars($bill['BillNumber']) ?>">
</div>

<!-- Bill Received Date -->
<div class="col-md-6">
<label class="form-label">Bill Received Date</label>
<input type="date" name="BillReceivedDate" class="form-control" required value="<?= $bill['BillReceivedDate'] ?>">
</div>

<!-- Received From Section -->
<div class="col-md-6">
<label class="form-label">Received From Section</label>
<input type="text" name="ReceivedFromSection" class="form-control" required value="<?= htmlspecialchars($bill['ReceivedFromSection']) ?>">
</div>

<!-- Section DA -->
<div class="col-md-6">
<label class="form-label">Section DA Name</label>
<input type="text" name="SectionDAName" class="form-control" value="<?= htmlspecialchars($bill['SectionDAName']) ?>">
</div>

<!-- Bill Type -->
<div class="col-md-6">
<label class="form-label">Bill Type</label>
<select name="BillTypeId" class="form-select" required>
<option value="">Select</option>
<?php foreach($billTypes as $b): ?>
<option value="<?= $b['Id'] ?>" <?= ($b['Id'] == $bill['BillTypeId'])?'selected':'' ?>>
<?= htmlspecialchars($b['BillType']) ?></option>
<?php endforeach; ?>
</select>
</div>

<!-- PFMS -->
<div class="col-md-6">
<label class="form-label">PFMS Unique Number</label>
<input type="text" name="PFMSUniqueNo" class="form-control" value="<?= htmlspecialchars($bill['PFMSUniqueNo']) ?>">
</div>

<!-- PO Number -->
<div class="col-md-6">
<label class="form-label">PO Order Number</label>
<input type="text" name="PONumber" class="form-control" value="<?= htmlspecialchars($bill['POOrderNo']) ?>">
</div>
<div class="col-md-6">
<label class="form-label">PO Order Date</label>
<input type="date" name="PODate" class="form-control" value="<?= $bill['POOrderDate'] ?>">
</div>

<!-- IT -->
<div class="col-md-4">
<label class="form-label">IT</label>
<input type="number" step="0.01" name="IT" class="form-control" value="<?= $bill['IT'] ?>">
</div>

<!-- GST -->
<div class="col-md-4">
<label class="form-label">GST</label>
<input type="number" step="0.01" name="GST" class="form-control" value="<?= $bill['GST'] ?>">
</div>

<!-- TDS -->
<div class="col-md-4">
<label class="form-label">TDS Type</label>
<select name="TDSType" class="form-select">
<option value="">None</option>
<option value="GST" <?= ($bill['TDS_Type']=='GST')?'selected':'' ?>>GST</option>
<option value="IT" <?= ($bill['TDS_Type']=='IT')?'selected':'' ?>>IT</option>
</select>
</div>

<!-- Invoices -->
<div class="col-12">
<label class="form-label fw-bold mb-2">Attach Invoices</label>
<div class="invoice-box">
<?php foreach($invoices as $i): ?>
<div class="form-check mb-2">
<input class="form-check-input" type="checkbox" name="Invoices[]" value="<?= $i['Id'] ?>" id="inv<?= $i['Id'] ?>" 
<?= ($i['BillInitialId']==$billId)?'checked':'' ?>>
<label class="form-check-label" for="inv<?= $i['Id'] ?>">
<strong><?= htmlspecialchars($i['InvoiceNo']) ?></strong>
<span class="text-muted"> | <?= htmlspecialchars($i['VendorName']) ?></span>
</label>
</div>
<?php endforeach; ?>
</div>
<small class="text-muted">✔ Select one or more invoices</small>
</div>

</div>

<div class="mt-4 text-end">
<button class="btn btn-success"><i class="fa fa-save me-1"></i> Update Bill Details</button>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function(){
    $("#billDetailsForm").on("submit", function(e){
        e.preventDefault();
        $.ajax({
            url: "bill_details_update.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success:function(res){
                if(res.status === "success"){
                    Swal.fire({
                        icon:'success',
                        title:'Updated',
                        text:'Bill details updated successfully',
                        timer:1500,
                        showConfirmButton:false
                    });
                }else{
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error:function(xhr){
                Swal.fire('Server Error','Check PHP error / DB connection','error');
            }
        });
    });
});
</script>
</body>
</html>

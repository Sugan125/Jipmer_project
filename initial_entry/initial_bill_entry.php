<?php
include '../config/db.php';
include '../includes/auth.php';

/* Fetch invoices */
$invoices = $conn->query("
    SELECT Id, InvoiceNumber, VendorName, InvoiceDate 
    FROM invoice_master 
    WHERE Status = 'Open'
    ORDER BY InvoiceDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Initial Bill Entry</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>

<style>
.page-content{
    margin-left:240px;
    padding:40px;
}
.card{
    max-width:950px;
    margin:auto;
}
.invoice-box{
    max-height:250px;
    overflow-y:auto;
    border:1px solid #dee2e6;
    padding:10px;
}
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card shadow p-4">

<h4 class="mb-4 text-primary">
<i class="fa fa-layer-group"></i> Initial Bill Entry
</h4>

<form id="initialBillForm">

<!-- ================= BILL DETAILS ================= -->
<div class="row g-3">

<div class="col-md-4">
<label>Bill Number</label>
<input type="text" name="BillNumber" class="form-control" required>
</div>

<div class="col-md-4">
<label>PFMS Unique Number</label>
<input type="text" name="PFMSUniqueNumber" class="form-control" required>
</div>

<div class="col-md-4">
<label>Bill Amount</label>
<input type="number" step="0.01" name="BillAmount" class="form-control" required>
</div>

</div>

<hr>

<!-- ================= INVOICE SELECTION ================= -->
<h6 class="text-secondary mb-2">Select Invoices (Max 6)</h6>

<div class="invoice-box">
<?php foreach($invoices as $i): ?>
<div class="form-check">
  <input class="form-check-input invoice-check" 
         type="checkbox" 
         name="InvoiceIds[]" 
         value="<?= $i['Id'] ?>">
  <label class="form-check-label">
    <b><?= htmlspecialchars($i['InvoiceNumber']) ?></b> |
    <?= htmlspecialchars($i['VendorName']) ?> |
    <?= date('d-m-Y', strtotime($i['InvoiceDate'])) ?>
  </label>
</div>
<?php endforeach; ?>
</div>

<div class="text-end mt-4">
<button class="btn btn-success px-4">
<i class="fa fa-save"></i> Save Initial Bill
</button>
</div>

</form>
</div>
</div>

<script>
$('.invoice-check').on('change', function(){
    if($('.invoice-check:checked').length > 6){
        alert('Maximum 6 invoices allowed');
        this.checked = false;
    }
});

$('#initialBillForm').submit(function(e){
    e.preventDefault();

    $.post('bill_initial_submit.php', $(this).serialize(), function(r){
        if(r.status === 'success'){
            alert('Initial Bill Saved Successfully');
            window.location.href = 'bill_entry_add.php?initial_id='+r.id;
        }else{
            alert(r.message);
        }
    },'json');
});
</script>

</body>
</html>

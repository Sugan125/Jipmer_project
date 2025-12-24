<?php
include '../config/db.php';
include '../includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Invoice</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-5">
<div class="card shadow p-4 col-md-8 mx-auto">

<h4 class="text-primary mb-4">🧾 Invoice Entry</h4>

<form id="invoiceForm">
<div class="row g-3">

<div class="col-md-6">
<label>Sanction Order</label>
<input type="text" name="SanctionOrder" class="form-control" required>
</div>

<div class="col-md-6">
<label>Sanction Date</label>
<input type="date" name="SanctionDate" class="form-control" required>
</div>

<div class="col-md-6">
<label>Invoice Number</label>
<input type="text" name="InvoiceNumber" class="form-control" required>
</div>

<div class="col-md-6">
<label>Invoice Date</label>
<input type="date" name="InvoiceDate" class="form-control" required>
</div>

<div class="col-md-6">
<label>Vendor Name</label>
<input type="text" name="VendorName" class="form-control" required>
</div>

<div class="col-md-6">
<label>Account Details</label>
<input type="text" name="AccountDetails" class="form-control">
</div>

</div>

<div class="text-end mt-4">
<button class="btn btn-success">Save Invoice</button>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$('#invoiceForm').submit(function(e){
e.preventDefault();
$.post('invoice_submit.php', $(this).serialize(), function(r){
    if(r.status=='success'){
        Swal.fire('Saved','Invoice added','success');
        $('#invoiceForm')[0].reset();
    }else{
        Swal.fire('Error',r.message,'error');
    }
},'json');
});
</script>
</body>
</html>

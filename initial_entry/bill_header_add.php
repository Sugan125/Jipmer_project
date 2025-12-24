<?php
include '../config/db.php';
include '../includes/auth.php';

$invoices = $conn->query("
SELECT * FROM invoice_master WHERE Status='Open'
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Create Bill</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-5">
<div class="card shadow p-4 col-md-9 mx-auto">

<h4 class="text-primary mb-4">📄 Bill Creation</h4>

<form id="billForm">

<div class="row g-3 mb-4">
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

<h6>Select Invoices (Max 6)</h6>
<table class="table table-bordered">
<?php foreach($invoices as $i): ?>
<tr>
<td>
<input type="checkbox" name="invoices[]" value="<?= $i['Id'] ?>">
</td>
<td><?= $i['InvoiceNumber'] ?></td>
<td><?= $i['VendorName'] ?></td>
<td><?= $i['InvoiceDate'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<div class="text-end">
<button class="btn btn-success">Create Bill</button>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$('#billForm').submit(function(e){
e.preventDefault();
let count = $('input[name="invoices[]"]:checked').length;
if(count==0 || count>6){
    Swal.fire('Select 1 to 6 invoices');
    return;
}

$.post('bill_header_submit.php', $(this).serialize(), function(r){
    if(r.status=='success'){
        Swal.fire('Success','Bill created','success')
        .then(()=> location.href='bill_entry_add.php?id='+r.bill_id);
    }
},'json');
});
</script>
</body>
</html>

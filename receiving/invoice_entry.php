<?php
include '../config/db.php';
include '../includes/auth.php';

/* ===== Permission Check ===== */
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);

/* ===== Dropdown Data ===== */
$billTypes = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1")->fetchAll(PDO::FETCH_ASSOC);
$invoices = $conn->query("
    SELECT i.*
FROM invoice_master i
LEFT JOIN bill_invoice_map bim ON i.Id = bim.InvoiceId
WHERE bim.InvoiceId IS NULL
ORDER BY i.InvoiceNo
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Details</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link href="../js/select2/select2.min.css" rel="stylesheet">
<script src="../js/select2/select2.min.js"></script>
<style>
.page-content{
    margin-left:240px;
    padding:90px 30px 30px;
}
.card{
    max-width:900px;
    margin:auto;
}
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<div class="card shadow-sm p-4">
<h4 class="mb-4 text-primary">
<i class="fa fa-file-invoice me-2"></i> Invoice Entry
</h4>
<form id="invoiceForm">
<div class="row g-3">
<div class="col-md-4">
<label>Invoice No</label>
<input type="text" name="InvoiceNo" class="form-control" required>
</div>

<div class="col-md-4">
<label>Invoice Date</label>
<input type="date" name="InvoiceDate" class="form-control" required>
</div>

<div class="col-md-4">
<label>Sanction Order No</label>
<input type="text" name="SanctionOrderNo" class="form-control">
</div>

<div class="col-md-4">
<label>Sanction Date</label>
<input type="date" name="SanctionDate" class="form-control">
</div>

<div class="col-md-4">
<label>Vendor Name</label>
<input type="text" name="VendorName" class="form-control">
</div>

<div class="col-md-4">
<label>Account Details</label>
<input type="text" name="AccountDetails" class="form-control">
</div>
</div>

<button class="btn btn-primary mt-4">Save Invoice</button>
</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$("#invoiceForm").submit(function(e){
e.preventDefault();
$.post("invoice_submit.php",$(this).serialize(),function(r){
if(r.status=="success"){
Swal.fire("Saved","Invoice added","success");
$("#invoiceForm")[0].reset();
}else{
Swal.fire("Error",r.message,"error");
}
},"json");
});
</script>
</body>
</html>

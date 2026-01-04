<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';

// Fetch invoice list
$invoices = $conn->query("
    SELECT im.Id, im.InvoiceNo, im.InvoiceDate, im.VendorName, d.DeptName, im.TotalAmount
    FROM invoice_master im
    LEFT JOIN dept_master d ON im.DeptId = d.Id
    ORDER BY im.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Invoice List</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1200px;margin:auto;}
.modal-header{background:#007bff;color:#fff;}
.modal-body .row>div{margin-bottom:10px;}
.view-btn{cursor:pointer;color:#007bff;}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">
<h4 class="text-primary mb-4"><i class="fa fa-list"></i> Invoice List</h4>

<table id="invoiceTable" class="table table-striped table-bordered">
<thead>
<tr>
<th>#</th>
<th>Invoice No</th>
<th>Invoice Date</th>
<th>Vendor</th>
<th>Department</th>
<th>Total Amount</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($invoices as $i => $inv): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= $inv['InvoiceNo'] ?></td>
<td><?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></td>
<td><?= $inv['VendorName'] ?></td>
<td><?= $inv['DeptName'] ?></td>
<td><?= number_format(!empty($inv['TotalAmount']) ? $inv['TotalAmount'] : 0, 2) ?></td>

<td><i class="fa fa-eye view-btn" data-id="<?= $inv['Id'] ?>" title="View Invoice"></i></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl">
<div class="modal-content">
<div class="modal-header bg-primary text-white">
    <h5 class="modal-title"><i class="fa fa-file-invoice"></i> Invoice Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<div id="invoiceDetails" class="p-3">
    <div class="text-center text-muted">Loading...</div>
</div>
</div>
<div class="modal-footer">
    <a href="#" id="fullPageView" target="_blank" class="btn btn-success"><i class="fa fa-external-link-alt"></i> View Full Page</a>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function(){
    var table = $('#invoiceTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10
    });

    // Event delegation for view button
    $('#invoiceTable').on('click', '.view-btn', function(){
        var invoiceId = $(this).data('id');
        $('#invoiceDetails').html('<div class="text-center text-muted">Loading...</div>');

        // Show modal
        var invoiceModal = new bootstrap.Modal(document.getElementById('invoiceModal'));
        invoiceModal.show();

        // Load invoice details with tabs
        $.get('invoice_view.php', {id: invoiceId}, function(res){
            $('#invoiceDetails').html(res);
        });
    });
});
</script>

</body>
</html>

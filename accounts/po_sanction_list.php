<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>PO & Sanction Orders List</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1200px;margin:auto;}
.table td, .table th{vertical-align:middle;}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">
<h4 class="text-primary mb-4"><i class="fa fa-list"></i> PO & Sanction Orders List</h4>

<div class="text-end mb-3">
    <a href="po_sanction_entry.php" class="btn btn-success"><i class="fa fa-plus"></i> Add New PO</a>
</div>

<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>PO Number</th>
    <th>PO Date</th>
    <th>PO Amount</th>
    <th>PO Net Total</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php
$stmt = $conn->query("SELECT * FROM po_master ORDER BY Id DESC");
$pos = $stmt->fetchAll();
$i = 1;
foreach($pos as $po):
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($po['POOrderNo']) ?></td>
    <td><?= htmlspecialchars($po['POOrderDate']) ?></td>
    <td><?= number_format($po['POAmount'],2) ?></td>
    <td><?= number_format($po['PONetAmount'],2) ?></td>
    <td>
        <a href="po_sanction_entry_edit.php?po_id=<?= $po['Id'] ?>" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i> Edit</a>
        <button class="btn btn-sm btn-danger deletePO" data-id="<?= $po['Id'] ?>"><i class="fa fa-trash"></i> Delete</button>
        <a href="po_sanction_details.php?po_id=<?= $po['Id'] ?>" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
    $('.deletePO').click(function(){
        var poId = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Are you sure?',
            text: "This will delete the PO and all its sanctions!",
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if(result.isConfirmed){
                $.ajax({
                    url: 'po_sanction_delete.php',
                    type: 'POST',
                    data: {po_id: poId},
                    dataType: 'json',
                    success: function(res){
                        if(res.status === 'success'){
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'PO and its sanctions deleted successfully.'
                            }).then(()=> location.reload());
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(err){
                        Swal.fire('Error', 'AJAX Error', 'error');
                    }
                });
            }
        });
    });
});
</script>

</body>
</html>

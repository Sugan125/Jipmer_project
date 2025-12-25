<?php
include '../config/db.php';
include '../includes/auth.php';

/* ===== Fetch All Bills ===== */
$rows = $conn->query("
    SELECT b.Id, b.BillNumber, b.BillReceivedDate, b.ReceivedFromSection,
           bt.BillType,be.Status
    FROM bill_initial_entry b
    left join bill_entry be on be.BillInitialId = b.Id
    LEFT JOIN bill_type_master bt ON b.BillTypeId = bt.Id
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Bills</title>

<!-- Bootstrap -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">

<!-- DataTables CSS -->
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="../css/style.css">

</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container" style="margin-top:80px;">
    <div class="card shadow-sm p-4">
        <h4 class="text-primary fw-bold mb-4">
            📄 All Bills
        </h4>

<table id="billTable" class="table table-striped table-bordered">
<thead class="table-primary">
<tr>
<th>#</th>
<th>Bill Number</th>
<th>Bill Type</th>
<th>Received Date</th>
<th>From Section</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?= $r['Id'] ?></td>
<td><?= htmlspecialchars($r['BillNumber']) ?></td>
<td><?= htmlspecialchars($r['BillType']) ?></td>
<td><?= $r['BillReceivedDate'] ?></td>
<td><?= htmlspecialchars($r['ReceivedFromSection']) ?></td>
<td>

<?php if ($r['Status'] === NULL): ?>

    <a href="edit_bill_details.php?id=<?= $r['Id'] ?>"
       class="btn btn-sm btn-success">
       <i class="fa fa-edit"></i> Edit
    </a>

    <button class="btn btn-sm btn-danger deleteBill"
            data-id="<?= $r['Id'] ?>">
        <i class="fa fa-trash"></i> Delete
    </button>

<?php else: ?>

    <button class="btn btn-sm btn-success" disabled>
        <i class="fa fa-edit"></i> Edit
    </button>

    <button class="btn btn-sm btn-danger" disabled>
        <i class="fa fa-trash"></i> Delete
    </button>

<?php endif; ?>

</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>
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
    $('#billTable').DataTable({
        pageLength: 10,
        order: [[0,'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: '📥 Export Excel', className:'btn btn-success btn-sm' },
            { extend: 'pdf', text: '📄 Export PDF', className:'btn btn-danger btn-sm' },
            { extend: 'print', text: '🖨 Print', className:'btn btn-secondary btn-sm' }
        ]
    });

     $('.deleteBill').click(function(){
        let billId = $(this).data('id');

        Swal.fire({
            title: 'Delete this bill?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if(result.isConfirmed){
                $.post(
                    'bill_delete.php',
                    { id: billId },
                    function(res){
                        if(res.status === 'success'){
                            Swal.fire('Deleted!', res.message, 'success')
                                .then(()=> location.reload());
                        }else{
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    'json'
                );
            }
        });
    });
});
</script>

</body>
</html>

<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(3);

// Get bills which are Passed and NOT yet in transactions
$rows = $conn->query("
    SELECT b.Id, b.BillNo, p.TotalAmount
    FROM bill_entry b
    LEFT JOIN bill_process p ON p.BillId = b.Id
    WHERE b.Status='Pass'
    AND NOT EXISTS (SELECT 1 FROM bill_transactions t WHERE t.BillId = b.Id)
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);

include '../header/header_accounts.php';
?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">Transaction / Batch Entry</h3>

    <div class="table-responsive shadow rounded">
        <table id="transactionsTable" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Bill ID</th>
                    <th>Bill No</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= $r['Id'] ?></td>
                    <td><?= htmlspecialchars($r['BillNo']) ?></td>
                    <td><?= htmlspecialchars($r['TotalAmount']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary add-transaction-btn" data-id="<?= $r['Id'] ?>">
                            Add Transaction
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JS/CSS libraries -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
    $('#transactionsTable').DataTable({
        "lengthMenu": [5,10,25],
        "pageLength": 10
    });

    $('.add-transaction-btn').click(function() {
        var billId = $(this).data('id');

        Swal.fire({
            title: 'Transaction Entry',
            html:
                '<input id="transactionNo" class="swal2-input" placeholder="Transaction No">' +
                '<input id="batchNo" class="swal2-input" placeholder="Batch No">',
            confirmButtonText: 'Save',
            focusConfirm: false,
            preConfirm: () => {
                return [
                    $('#transactionNo').val(),
                    $('#batchNo').val()
                ]
            }
        }).then((result) => {
            if(result.isConfirmed){
                var txn = result.value[0];
                var batch = result.value[1];

                if(!txn || !batch){
                    Swal.fire('Error','Transaction No & Batch No required','error');
                    return;
                }

                $.post('transaction_add_ajax.php', {
                    bill_id: billId,
                    transaction_no: txn,
                    batch_no: batch
                }, function(resp){
                    if(resp.status==='success'){
                        Swal.fire('Saved!',resp.message,'success').then(()=> location.reload());
                    } else {
                        Swal.fire('Error',resp.message,'error');
                    }
                }, 'json');
            }
        });
    });
});
</script>

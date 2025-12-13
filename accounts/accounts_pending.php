<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(3);

// bills with Pass status and not yet in final_accounts
$rows = $conn->query("
    SELECT b.*, p.TotalAmount, p.Status as ProcessStatus
    FROM bill_entry b
    LEFT JOIN bill_process p ON p.BillId = b.Id
    INNER JOIN bill_transactions t ON t.BillId = b.Id
    WHERE b.Status = 'Pass'
    AND NOT EXISTS (SELECT 1 FROM final_accounts f WHERE f.BillId = b.Id)
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../header/header_accounts.php'; ?>

<div class="container mt-5">
    <h3 class="mb-4 text-center">Bills Awaiting Voucher (PFMS)</h3>

    <div class="table-responsive shadow rounded">
        <table id="billsTable" class="table table-striped table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
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
                        <button class="btn btn-sm btn-primary enter-voucher-btn" data-id="<?= $r['Id'] ?>">
                            <i class="fas fa-file-invoice-dollar"></i> Enter Voucher
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
    // Initialize DataTable
    $('#billsTable').DataTable({
        "lengthMenu": [5, 10, 25, 50],
        "pageLength": 10,
        "ordering": true,
        "columnDefs": [
            { "orderable": false, "targets": 3 } // Disable sorting on Action column
        ]
    });

    // Handle Enter Voucher via AJAX POST
   $('.enter-voucher-btn').click(function() {
    var billId = $(this).data('id');

    Swal.fire({
        title: 'Enter Voucher?',
        text: "You are about to enter voucher for this bill.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<i class="fas fa-check-circle"></i> Proceed'
    }).then((result) => {
        if(result.isConfirmed){
            // Create a temporary POST form
            var form = $('<form action="voucher_add.php" method="POST">' +
                         '<input type="hidden" name="bill_id" value="' + billId + '"></form>');
            $('body').append(form);
            form.submit();
        }
    });
});

});
</script>

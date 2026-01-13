<?php
include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) die("Unauthorized Access");

// Get bills which are Passed and NOT yet in transactions
$rows = $conn->query("
    SELECT 
        b.Id,
        bi.BillNumber,
        bi.BillReceivedDate,
        bi.TotalAmount,
        ia.ReceivedFromSection
    FROM bill_entry b
    INNER JOIN bill_initial_entry bi
        ON bi.Id = b.BillInitialId
    INNER JOIN bill_process p
        ON p.BillId = bi.Id
       AND p.Status = 'Pass'
    LEFT JOIN (
        SELECT 
            bim.BillInitialId,
            MAX(im.ReceivedFromSection) AS ReceivedFromSection
        FROM bill_invoice_map bim
        INNER JOIN invoice_master im
            ON im.Id = bim.InvoiceId
        GROUP BY bim.BillInitialId
    ) ia ON ia.BillInitialId = bi.Id
    WHERE b.Status = 'Pass'
      AND NOT EXISTS (
            SELECT 1
            FROM bill_transactions t
            WHERE t.BillId = b.Id
      )
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transaction / Batch Entry</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<style>
body { margin: 0; min-height: 100vh; background-color: #f8f9fa; }
.topbar-fixed { position: fixed; top: 0; width: 100%; z-index: 1030; }
.sidebar-fixed { position: fixed; top: 70px; bottom: 0; width: 240px; overflow-y: auto; background-color: #343a40; }
.page-content { margin-left: 240px; padding: 100px 20px 20px 20px; }
.table-responsive { max-width: 1000px; margin: auto; }
</style>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <h3 class="mb-4 text-center">Transaction / Batch Entry</h3>

    <div class="table-responsive shadow rounded bg-white p-3">
        <table id="transactionsTable" class="table table-striped table-bordered align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>Bill ID</th>
                    <th>Bill No</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody class="text-center">
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= $r['Id'] ?></td>
                    <td><?= htmlspecialchars($r['BillNumber']) ?></td>
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
        showCancelButton: true,            // ✅ Show cancel button
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',        // ✅ Text for cancel button
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
        // ✅ If user clicked Cancel, nothing happens automatically
    });
});

});
</script>

</body>
</html>

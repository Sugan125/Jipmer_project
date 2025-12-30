<?php
include '../config/db.php';
include '../includes/auth.php';

/* ===== Fetch All Bills ===== */
$bills = $conn->query("
    SELECT Id, BillNumber, BillReceivedDate, ReceivedFromSection, Status
    FROM bill_initial_entry
    ORDER BY CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Bills</title>

<!-- Bootstrap & FontAwesome -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<style>
.page-content { margin-left:240px; padding:50px 30px; }
.card { max-width:1200px; margin:auto; }
.modal-lg { max-width: 90% !important; }
.invoice-card { border:1px solid #ddd; padding:15px; margin-bottom:10px; border-radius:8px; background:#f9f9f9; }
.invoice-header { background:#007bff; color:#fff; padding:8px 15px; border-radius:5px; margin-bottom:10px; }
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <div class="card shadow-sm p-4">
        <h4 class="text-primary mb-4"><i class="fa fa-file-invoice"></i> All Bills</h4>

        <table id="billTable" class="table table-striped table-bordered">
            <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Bill Number</th>
                <th>Received Date</th>
                <th>From Section</th>
                <th>Status</th>
                <th>Invoices</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($bills as $b): ?>
                <tr>
                    <td><?= $b['Id'] ?></td>
                    <td><?= htmlspecialchars($b['BillNumber']) ?></td>
                    <td><?= date('d-m-Y', strtotime($b['BillReceivedDate'])) ?></td>
                    <td><?= htmlspecialchars($b['ReceivedFromSection']) ?></td>
                    <td><?= htmlspecialchars($b['Status'] ?? 'DRAFT') ?></td>
                    <td>
                        <button class="btn btn-info btn-sm viewInvoices" data-id="<?= $b['Id'] ?>">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-file-invoice"></i> Attached Invoices</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="invoiceDetails">
                <div class="text-center text-muted">Loading...</div>
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
    $('#billTable').DataTable({
        pageLength: 10,
        order: [[0,'desc']]
    });

    $('.viewInvoices').click(function(){
        let billId = $(this).data('id');
        $('#invoiceDetails').html('<div class="text-center text-muted">Loading...</div>');
        $('#invoiceModal').modal('show');

        $.get('bill_invoices_ajax.php', {id: billId}, function(html){
            $('#invoiceDetails').html(html);
        });
    });
});
</script>

</body>
</html>

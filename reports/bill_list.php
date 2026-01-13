<?php
include '../config/db.php';
include '../includes/auth.php';

/* ===== Fetch All Bills with totals and alloted employee ===== */
$bills = $conn->query("
   SELECT 
    bi.Id,
    bi.BillNumber,
    bi.BillReceivedDate,
    MAX(im.ReceivedFromSection) AS ReceivedFromSection,
    bi.Status,
    MAX(e.EmployeeName) AS AllotedTo,
    MAX(btm.BillType) AS BillType,

    -- Totals
    SUM(im.TotalAmount) AS TotalAmount,
    SUM(im.GSTAmount) AS TotalGST,
    SUM(im.ITAmount) AS TotalIT,
    SUM(im.TDS) AS TotalTDS,
    SUM(
        im.TotalAmount 
        + COALESCE(im.GSTAmount,0) 
        - COALESCE(im.ITAmount,0) 
        - COALESCE(im.TDS,0)
    ) AS NetAmount

FROM bill_initial_entry bi
LEFT JOIN bill_entry be 
    ON be.BillInitialId = bi.Id
LEFT JOIN employee_master e 
    ON be.AllotedDealingAsst = e.Id
LEFT JOIN bill_invoice_map bim 
    ON bim.BillInitialId = bi.Id
LEFT JOIN invoice_master im 
    ON im.Id = bim.InvoiceId
LEFT JOIN bill_type_master btm 
    ON btm.Id = im.BillTypeId

GROUP BY 
    bi.Id,
    bi.BillNumber,
    bi.BillReceivedDate,
    bi.Status

ORDER BY MAX(bi.CreatedDate) DESC;
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Bills</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<style>
.page-content { margin-left:240px; padding:50px 30px; }
.card { max-width:1400px; margin:auto; }
.modal-lg { max-width: 90% !important; }
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
                <th>Bill Type</th>
                <th>Received Date</th>
                <th>From Section</th>
                <th>Alloted To</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Total GST</th>
                <th>Total IT</th>
                <th>Total TDS</th>
                <th>Net Amount</th>
                <th>Invoices</th>
                <th>Bill PDF</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($bills as $b): ?>
                <tr>
                    <td><?= $b['Id'] ?></td>
                    <td><?= htmlspecialchars($b['BillNumber']) ?></td>
                    <td><?= htmlspecialchars($b['BillType'] ?? '-') ?></td>
                    <td><?= $b['BillReceivedDate'] ? date('d-m-Y', strtotime($b['BillReceivedDate'])) : '-' ?></td>
                    <td><?= htmlspecialchars($b['ReceivedFromSection']) ?></td>
                    <td><?= htmlspecialchars($b['AllotedTo'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($b['Status'] ?? 'DRAFT') ?></td>
                    <td class="text-end"><?= number_format($b['TotalAmount'] ?? 0,2) ?></td>
                    <td class="text-end"><?= number_format($b['TotalGST'] ?? 0,2) ?></td>
                    <td class="text-end"><?= number_format($b['TotalIT'] ?? 0,2) ?></td>
                    <td class="text-end"><?= number_format($b['TotalTDS'] ?? 0,2) ?></td>
                    <td class="text-end"><?= number_format($b['NetAmount'] ?? 0,2) ?></td>
                    <td>
                        <button class="btn btn-info btn-sm viewInvoices" data-id="<?= $b['Id'] ?>">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </td>

                    <td>
                        <form method="post" action="generate_bill_pdf.php" target="_blank">
                            <input type="hidden" name="bill_id" value="<?= $b['Id'] ?>">
                            <button class="btn btn-success btn-sm">
                                <i class="fa fa-file-pdf"></i> PDF
                            </button>
                        </form>
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

        // Load invoice_view.php in modal
        $.get('bill_invoices_ajax.php', {id: billId}, function(html){
            $('#invoiceDetails').html(html);
        });
    });
});
</script>

</body>
</html>

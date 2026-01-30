<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

function nf($v){ return number_format((float)($v ?? 0), 2); }
function df($d){ return $d ? date('d-m-Y', strtotime($d)) : '-'; }

/*
  Bills are stored in: bill_initial_entry (one bill)
  Invoices mapped in: bill_invoice_map (BillInitialId, InvoiceId)
  Invoice values in: invoice_master (NetPayable, TotalAmount, TDSGSTAmount, TDSITAmount, TDSITAmount etc.)
*/

$bills = $conn->query("
    SELECT 
        bi.Id,
        bi.BillNumber,
        bi.BillReceivedDate,
        bi.Status,
        bi.CreatedDate,

        -- Alloted info
        MAX(be.AllotedDate) AS AllotedDate,
        MAX(e.EmployeeName) AS AllotedTo,

        -- Section name (from invoices under this bill)
        MAX(im.ReceivedFromSection) AS ReceivedFromSection,

        -- Totals computed from invoice_master via mapping
        COUNT(DISTINCT im.Id) AS InvoiceCount,
        SUM(ISNULL(im.TotalAmount,0))   AS TotalAmount,
        SUM(ISNULL(im.TDSGSTAmount,0))  AS TotalGST,
        SUM(ISNULL(im.TDSITAmount,0))   AS TotalIT,
        SUM(ISNULL(im.TDS,0))           AS TotalTDS,
        SUM(ISNULL(im.NetPayable,0))    AS NetAmount

    FROM bill_initial_entry bi
    LEFT JOIN bill_entry be ON be.BillInitialId = bi.Id
    LEFT JOIN employee_master e ON e.Id = be.AllotedDealingAsst

    LEFT JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    LEFT JOIN invoice_master im ON im.Id = bim.InvoiceId

    GROUP BY
        bi.Id, bi.BillNumber, bi.BillReceivedDate, bi.Status, bi.CreatedDate

    ORDER BY bi.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill List</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<style>
.page-content { margin-left:240px; padding:50px 30px; }
.card { max-width:1500px; margin:auto; }

.badge-soft {
    background:#eef4ff; color:#0d6efd;
    border:1px solid #cfe2ff;
    font-weight:600;
}
.table td, .table th { vertical-align: middle; }
.text-small { font-size: 12px; color:#6c757d; }
.modal-lg { max-width: 92% !important; }
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <div class="card shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="text-primary mb-0"><i class="fa fa-file-invoice"></i> Bill List</h4>
            <div class="badge badge-soft">Total Bills: <?= count($bills) ?></div>
        </div>

        <div class="table-responsive">
        <table id="billTable" class="table table-striped table-bordered">
            <thead class="table-light">
            <tr>
                <th>Bill ID</th>
                <th>Bill No</th>
                <th>Received Date</th>
                <th>From Section</th>
                <th>Alloted To</th>
                <th>Status</th>

                <th class="text-end">Invoices</th>
                <th class="text-end">Total Amount</th>
                <th class="text-end">TDS GST</th>
                <th class="text-end">TDS IT</th>
                <th class="text-end">Total TDS</th>
                <th class="text-end">Net Amount</th>

                <th>View</th>
                <th>PDF</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($bills as $b): ?>
                <tr>
                    <td><?= (int)$b['Id'] ?></td>

                    <td>
                        <div class="fw-bold"><?= htmlspecialchars($b['BillNumber'] ?? '-') ?></div>
                        <div class="text-small">Created: <?= df($b['CreatedDate'] ?? null) ?></div>
                    </td>

                    <td><?= df($b['BillReceivedDate'] ?? null) ?></td>
                    <td><?= htmlspecialchars($b['ReceivedFromSection'] ?? '-') ?></td>

                    <td>
                        <?= htmlspecialchars($b['AllotedTo'] ?? '-') ?>
                        <div class="text-small">Alloted: <?= df($b['AllotedDate'] ?? null) ?></div>
                    </td>

                    <td>
                        <span class="badge bg-<?= ($b['Status']==='Pass' ? 'success' : ($b['Status']==='Return' ? 'danger' : 'secondary')) ?>">
                            <?= htmlspecialchars($b['Status'] ?? 'DRAFT') ?>
                        </span>
                    </td>

                    <td class="text-end fw-bold"><?= (int)($b['InvoiceCount'] ?? 0) ?></td>
                    <td class="text-end"><?= nf($b['TotalAmount'] ?? 0) ?></td>
                    <td class="text-end text-warning fw-bold"><?= nf($b['TotalGST'] ?? 0) ?></td>
                    <td class="text-end text-warning fw-bold"><?= nf($b['TotalIT'] ?? 0) ?></td>
                    <td class="text-end text-danger fw-bold"><?= nf($b['TotalTDS'] ?? 0) ?></td>
                    <td class="text-end text-success fw-bold"><?= nf($b['NetAmount'] ?? 0) ?></td>

                    <td>
                        <button class="btn btn-info btn-sm viewInvoices" data-id="<?= (int)$b['Id'] ?>">
                            <i class="fa fa-eye"></i> View Invoices
                        </button>
                    </td>

                    <td>
                        <form method="post" action="../reports/generate_bill_pdf.php" target="_blank">
                            <input type="hidden" name="bill_id" value="<?= (int)$b['Id'] ?>">
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
</div>

<!-- Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-file-invoice"></i> Bill Invoices Detail</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

<script>
$(document).ready(function(){

    $('#billTable').DataTable({
        pageLength: 10,
        order: [[0,'desc']]
    });

    $(document).on('click', '.viewInvoices', function(){
        let billId = $(this).data('id');
        $('#invoiceDetails').html('<div class="text-center text-muted">Loading...</div>');

        let modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
        modal.show();

        // bill_invoices_ajax.php will use ?id=BillInitialId (THIS is correct)
        $.get('bill_invoices_ajax.php', { id: billId }, function(html){
            $('#invoiceDetails').html(html);
        }).fail(function(xhr){
            $('#invoiceDetails').html('<div class="text-danger">Error loading invoices: '+xhr.status+'</div>');
        });
    });

});
</script>

</body>
</html>

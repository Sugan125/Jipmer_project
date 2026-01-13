<?php
include '../config/db.php';
include '../includes/auth.php';

$billId = intval($_POST['bill_id'] ?? 0);
if($billId <= 0){
    die('<script>alert("Invalid Bill ID: '.$billId.'"); window.location.href="process_list.php";</script>');
}

// Fetch bill_initial_entry + bill_entry + alloted employee
$stmt = $conn->prepare("
    SELECT 
        bi.*,
        be.Status AS EntryStatus,
        e.EmployeeName AS AllotedDealingAsstName,
        ia.ReceivedFromSection
    FROM bill_initial_entry bi
    LEFT JOIN bill_entry be 
        ON be.BillInitialId = bi.Id
    LEFT JOIN employee_master e 
        ON be.AllotedDealingAsst = e.Id
    LEFT JOIN (
        SELECT 
            bim.BillInitialId,
            MAX(im.ReceivedFromSection) AS ReceivedFromSection
        FROM bill_invoice_map bim
        JOIN invoice_master im ON im.Id = bim.InvoiceId
        GROUP BY bim.BillInitialId
    ) ia ON ia.BillInitialId = bi.Id
    WHERE bi.Id = ?
");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$bill){
    die('<script>alert("Bill not found! Bill ID: '.$billId.'"); window.location.href="process_list.php";</script>');
}

// Fetch attached invoices and calculate total
$stmt = $conn->prepare("
    SELECT im.*, d.DeptName, bi.Id as billId
    FROM invoice_master im
    LEFT JOIN bill_invoice_map bim ON bim.InvoiceId = im.Id
    LEFT JOIN dept_master d ON d.Id = im.DeptId
    LEFT JOIN bill_initial_entry bi ON bi.Id = bim.BillInitialId
    WHERE bim.BillInitialId = ?
    ORDER BY im.InvoiceDate DESC
");
$stmt->execute([$billId]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total amount
$totalAmount = 0;
foreach($invoices as $inv){
    $totalAmount += $inv['TotalAmount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Process Bill</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<style>
body { margin: 0; min-height: 100vh; background-color: #f8f9fa; }
.page-content { margin-left: 240px; padding: 80px 20px 20px 20px; }
.card { box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); }
.invoice-table th, .invoice-table td { vertical-align: middle; }
.invoice-table tbody tr:hover { background: #f9f9f9; }
</style>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <div class="card p-4 mb-4">
        <h4 class="text-primary mb-3"><i class="fas fa-file-invoice"></i> Process Bill #<?= htmlspecialchars($bill['BillNumber']) ?></h4>
        <div class="row mb-3">
            <div class="col-md-3"><strong>Received Date:</strong> <?= date('d-m-Y', strtotime($bill['BillReceivedDate'])) ?></div>
            <div class="col-md-3"><strong>Received From:</strong> <?= htmlspecialchars($bill['ReceivedFromSection']) ?></div>
            <div class="col-md-3"><strong>Alloted To:</strong> <?= htmlspecialchars($bill['AllotedDealingAsstName'] ?? '-') ?></div>
            <div class="col-md-3"><strong>Total Amount:</strong> <?= number_format($totalAmount,2) ?></div>
        </div>
    </div>

    <!-- Attached Invoices -->
    <div class="card p-4 mb-4">
        <h5 class="text-secondary mb-3"><i class="fas fa-receipt"></i> Attached Invoices</h5>
        <?php if($invoices): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped invoice-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Department</th>
                        <th>Total Amount</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($invoices as $i => $inv): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($inv['InvoiceNo']) ?></td>
                        <td><?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></td>
                        <td><?= htmlspecialchars($inv['VendorName']) ?></td>
                        <td><?= htmlspecialchars($inv['DeptName']) ?></td>
                        <td><?= number_format($inv['TotalAmount'],2) ?></td>
                          <td>
                        <button class="btn btn-info btn-sm viewInvoices" data-id="<?= $inv['billId'] ?>">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted">No invoices attached.</p>
        <?php endif; ?>
    </div>

    <!-- Process Form -->
    <div class="card p-4">
        <form id="processBillForm">
            <input type="hidden" name="bill_id" value="<?= $billId ?>">

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="">Select Status</option>
                    <option value="Pass">Pass</option>
                    <option value="Return">Return</option>
                </select>
            </div>

            <div class="mb-3" id="returnReasonDiv" style="display:none;">
                <label class="form-label">Reason for Return</label>
                <textarea name="reason" class="form-control" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Save
            </button>
            <a href="process_list.php" class="btn btn-secondary">Back</a>
        </form>
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
<script>
$(document).ready(function(){
    $('.viewInvoices').click(function(){
        let billId = $(this).data('id');
        $('#invoiceDetails').html('<div class="text-center text-muted">Loading...</div>');
        $('#invoiceModal').modal('show');

        $.get('../receiving/bill_invoices_ajax.php', {id: billId}, function(html){
            $('#invoiceDetails').html(html);
        });
    });
    $('#status').on('change', function(){
        if(this.value === 'Return'){
            $('#returnReasonDiv').show();
            $('#returnReasonDiv textarea').attr('required', true);
        } else {
            $('#returnReasonDiv').hide();
            $('#returnReasonDiv textarea').attr('required', false);
        }
    });

    $('#processBillForm').on('submit', function(e){
        e.preventDefault();
        $.ajax({
            url: 'bill_process_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: resp.message
                    }).then(()=>{ window.location.href='process_list.php'; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
                }
            },
            error: function(){
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
            }
        });
    });
});
</script>

</body>
</html>

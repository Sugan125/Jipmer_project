<?php
include '../config/db.php';
include '../includes/auth.php';

// Get bill id
$initial_id = intval($_GET['id'] ?? 0);
if(!$initial_id) exit('Invalid Bill ID');

// Fetch bill details
$stmt = $conn->prepare("SELECT * FROM bill_initial_entry WHERE Id=?");
$stmt->execute([$initial_id]);
$init = $stmt->fetch(PDO::FETCH_ASSOC);

$totalAmount = 0;
$totalGST = 0;
$totalIT = 0;
$totalTDS = 0;
$grossTotal = 0;
$netTotal = 0;



// Fetch attached invoices
$stmt = $conn->prepare("
    SELECT im.*, d.DeptName
    FROM invoice_master im
    JOIN bill_invoice_map bim ON bim.InvoiceId = im.Id
    LEFT JOIN dept_master d ON d.Id = im.DeptId
    WHERE bim.BillInitialId=?
    ORDER BY im.InvoiceDate DESC
");
$stmt->execute([$initial_id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch employees
$emps = $conn->query("SELECT Id, EmployeeName FROM employee_master WHERE Status=1 AND RoleId=2 ORDER BY EmployeeName")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Entry</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:60px 20px;}
.card-shadow{box-shadow:0 0.5rem 1rem rgba(0,0,0,.15);}
.invoice-table th, .invoice-table td{vertical-align: middle;}
.invoice-table tbody tr:hover{background:#f9f9f9;}
.btn-view{color:#0d6efd;}
.form-card{max-width:800px;margin:auto;}
.modal-xl .modal-body{max-height:70vh;overflow-y:auto;}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<!-- Bill Header -->
<div class="card card-shadow p-4 mb-4">
    <h4 class="text-primary mb-3"><i class="fa fa-file-invoice"></i> Bill Details - <?= htmlspecialchars($init['BillNumber']) ?></h4>
    <div class="row">
        <div class="col-md-4"><strong>Bill Number:</strong> <?= htmlspecialchars($init['BillNumber']) ?></div>
        <!-- <div class="col-md-4"><strong>Received From:</strong> <?= htmlspecialchars($init['ReceivedFromSection']) ?></div> -->
        <div class="col-md-4"><strong>Received Date:</strong> <?= $init['BillReceivedDate'] ?></div>
    </div>
</div>

<!-- Attached Invoices -->
<div class="card card-shadow p-4 mb-4">
    <h5 class="text-secondary mb-3"><i class="fa fa-receipt"></i> Attached Invoices</h5>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($invoices as $i => $inv): 
                $totalAmount += $inv['Amount'] ?? 0;
                $totalGST += $inv['GSTAmount'] ?? 0;
                $totalIT += $inv['ITAmount'] ?? 0;
                $totalTDS += $inv['TDS'] ?? 0;
                $grossTotal += $inv['TotalAmount'] ?? 0;
                // Net = TotalAmount + GST - IT - TDS (assuming this calculation)
                $netTotal += (($inv['TotalAmount'] ?? 0) + ($inv['GSTAmount'] ?? 0) - ($inv['ITAmount'] ?? 0) - ($inv['TDS'] ?? 0));
            ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($inv['InvoiceNo']) ?></td>
                    <td><?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></td>
                    <td><?= htmlspecialchars($inv['VendorName']) ?></td>
                    <td><?= htmlspecialchars($inv['DeptName']) ?></td>
                    <td><?= number_format($inv['TotalAmount'],2) ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-view" data-id="<?= $inv['Id'] ?>" data-bs-toggle="modal" data-bs-target="#invoiceModal">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if($invoices): ?>
<div class="mt-3 p-3 border bg-light">
    <h6 class="text-primary"><i class="fa fa-calculator"></i> Invoice Totals</h6>
    <div class="row">
        <div class="col-md-2"><strong>Total Amount:</strong> <?= number_format($totalAmount,2) ?></div>
        <div class="col-md-2"><strong>Total GST:</strong> <?= number_format($totalGST,2) ?></div>
        <div class="col-md-2"><strong>Total IT:</strong> <?= number_format($totalIT,2) ?></div>
        <div class="col-md-2"><strong>Total TDS:</strong> <?= number_format($totalTDS,2) ?></div>
        <div class="col-md-2"><strong>Gross Total:</strong> <?= number_format($grossTotal,2) ?></div>
        <div class="col-md-2"><strong>Net Total:</strong> <?= number_format($netTotal,2) ?></div>
    </div>
</div>
<?php endif; ?>
    </div>
    <?php else: ?>
        <p class="text-muted">No invoices attached.</p>
    <?php endif; ?>
</div>

<!-- Bill Entry Form -->
<div class="card card-shadow p-4 mb-5 form-card">
    <h4 class="text-primary mb-4"><i class="fa fa-edit"></i> Record Bill Entry</h4>
    <form id="billForm">
        <input type="hidden" name="initial_id" value="<?= $init['Id'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><i class="fa fa-hashtag"></i> Token No</label>
                <input type="text" name="tokno" class="form-control" placeholder="Enter token number" required>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="fa fa-user"></i> Alloted Dealing Assistant</label>
                <select name="alloted" class="form-select" required>
                    <option value="">Select</option>
                    <?php foreach($emps as $e): ?>
                        <option value="<?= $e['Id'] ?>"><?= htmlspecialchars($e['EmployeeName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label"><i class="fa fa-calendar-alt"></i> Allot Date</label>
                <input type="date" name="allotdate" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label"><i class="fa fa-sticky-note"></i> Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter remarks" required></textarea>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-between">
            <a href="bill_entry_list.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
            <button class="btn btn-primary"><i class="fa fa-save"></i> Save Bill</button>
        </div>
    </form>
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
<div class="modal-body" id="invoiceDetails">
    <div class="text-center text-muted">Loading...</div>
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
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function(){
    // Load invoice details dynamically in modal
    $('.btn-view').on('click', function(){
        var invoiceId = $(this).data('id');
        $('#invoiceDetails').html('<div class="text-center text-muted">Loading...</div>');
        $('#fullPageView').attr('href','invoice_full_view.php?id=' + invoiceId);

        $.get('invoice_view.php', {id: invoiceId}, function(res){
            $('#invoiceDetails').html(res);
        });
    });

    // Bill form submission
    $("#billForm").on("submit", function(e){
        e.preventDefault();
        $.ajax({
            url: "bill_entry_submit.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(r){
                if(r.status === "success"){
                    Swal.fire({
                        icon: "success",
                        title: "Saved",
                        text: "Bill successfully recorded.",
                        timer: 1500,
                        showConfirmButton: false,
                        willClose: () => { window.location.href = "bill_entry_list.php"; }
                    });
                } else {
                    Swal.fire("Error", r.message, "error");
                }
            },
            error: function(){
                Swal.fire("Server Error", "Unable to save right now.", "error");
            }
        });
    });
});
</script>
</body>
</html>

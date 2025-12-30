<?php
include '../config/db.php';
include '../includes/auth.php';

/* Fetch invoices not attached yet */
$invoices = $conn->query("
     SELECT i.Id, i.InvoiceNo, i.InvoiceDate, i.VendorName, d.DeptName, i.TotalAmount, fy.FinYear, h.SubDetailsHeadName
    FROM invoice_master i
    LEFT JOIN dept_master d ON i.DeptId = d.Id
    LEFT JOIN fin_year_master fy ON i.FinancialYearId = fy.Id
    LEFT JOIN hoa_master h ON i.HOAId = h.HoaId
    LEFT JOIN bill_invoice_map bim ON i.Id = bim.InvoiceId
    WHERE bim.BillInitialId IS NULL
    ORDER BY i.InvoiceNo
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Details Entry</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/datatables.min.css">
<style>
.page-content { margin-left:240px; padding:90px 30px 30px; }
.card { max-width:1100px; margin:auto; }
.table-fixed tbody { display:block; overflow-y:auto; }
.table-fixed tbody tr { display: table; width: 100%; table-layout: fixed; }
.table-fixed thead tr { display: table; width: 100%; table-layout: fixed; }
.table-fixed th, .table-fixed td { text-align:center; vertical-align:middle; }
.table-fixed th { background:#f8f9fa; }
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card shadow-sm p-4">
<h4 class="mb-4 text-primary"><i class="fa fa-file-invoice me-2"></i> Bill Details Entry</h4>

<form id="billDetailsForm">

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label fw-bold">Bill Number</label>
        <input type="text" name="BillNumber" class="form-control" placeholder="Enter Bill Number" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold">Bill Received Date</label>
        <input type="date" name="BillReceivedDate" class="form-control" required>
    </div>
</div>

<h5 class="mb-3">Attach Invoices</h5>
<div class="table-responsive">
    <table class="table table-bordered table-striped table-fixed">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll" title="Select All"></th>
                <th>Invoice No</th>
                <th>Invoice Date</th>
                <th>Financial Year</th>
                <th>HOA</th>
                <th>Vendor</th>
                <th>Department</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($invoices as $inv): ?>
            <tr>
                <td><input type="checkbox" class="invoice-checkbox" name="Invoices[]" value="<?= $inv['Id'] ?>"></td>
                <td><?= htmlspecialchars($inv['InvoiceNo']) ?></td>
                <td><?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></td>
                <td><?= htmlspecialchars($inv['FinYear'] ?? '-') ?></td>
                <td><?= htmlspecialchars($inv['SubDetailsHeadName'] ?? '-') ?></td>
                <td><?= htmlspecialchars($inv['VendorName']) ?></td>
                <td><?= htmlspecialchars($inv['DeptName']) ?></td>
                <td><?= number_format($inv['TotalAmount'],2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 text-end">
    <button class="btn btn-success btn-lg"><i class="fa fa-save me-1"></i> Save Bill Details</button>
</div>

</form>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function(){
    // Select / Deselect all invoices
    $('#selectAll').change(function(){
        $('.invoice-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Submit form
    $('#billDetailsForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: 'bill_details_submit.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: 'Bill details saved successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'bill_list.php';
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(){
                Swal.fire('Server Error', 'Check PHP error / DB connection', 'error');
            }
        });
    });
});
</script>
</body>
</html>

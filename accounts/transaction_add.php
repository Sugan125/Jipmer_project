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

/* ================= FETCH BILLS ================= */
$rows = $conn->query("
    SELECT 
        b.Id AS BillId,
        bi.BillNumber,
        bi.BillReceivedDate,
        bi.TotalAmount,
        im.VendorName,
        im.InvoiceNo,
        im.InvoiceDate,
        im.NetPayable,
        im.ReceivedFromSection
    FROM bill_entry b
    INNER JOIN bill_initial_entry bi ON bi.Id = b.BillInitialId
    INNER JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    INNER JOIN invoice_master im ON im.Id = bim.InvoiceId
    INNER JOIN bill_process p ON p.BillId = bi.Id AND p.Status='Pass'
    WHERE b.Status='Pass'
      AND NOT EXISTS (
        SELECT 1 FROM bill_transactions t WHERE t.BillId=b.Id
      )
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Batch Transaction Entry</title>

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

.page-content{margin-left:240px;padding:90px 20px}
.table thead th{vertical-align:middle;text-align:center}
.table td{text-align:center;vertical-align:middle}
.amount{text-align:right;font-weight:600}
.badge-section{background:#eef4ff;color:#0d6efd}
tfoot td{font-weight:700;background:#f1f3f5}
</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<h4 class="text-center mb-3">
<i class="fa fa-layer-group"></i> Batch Transaction Entry
</h4>

<div class="text-center mb-3">
    <button class="btn btn-success px-4" id="createBatch">
        <i class="fa fa-save"></i> Create Batch
    </button>
</div>

<div class="table-responsive bg-white shadow rounded p-3">

<table id="billTable" class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th><input type="checkbox" id="selectAll"></th>
    <th>Bill No</th>
    <th>Bill Date</th>
    <th>Vendor</th>
    <th>Invoice No</th>
    <th>Invoice Date</th>
    <th>Section</th>
    <th>Bill Amount</th>
    <th>Net Payable</th>
</tr>
</thead>

<tbody>
<?php foreach($rows as $r): ?>
<tr data-amount="<?= $r['NetPayable'] ?>">
    <td>
        <input type="checkbox" class="bill-check" value="<?= $r['BillId'] ?>">
    </td>
    <td><?= htmlspecialchars($r['BillNumber']) ?></td>
    <td><?= $r['BillReceivedDate'] ?></td>
    <td><?= htmlspecialchars($r['VendorName']) ?></td>
    <td><?= htmlspecialchars($r['InvoiceNo']) ?></td>
    <td><?= $r['InvoiceDate'] ?></td>
    <td>
        <span class="badge badge-section">
            <?= htmlspecialchars($r['ReceivedFromSection']) ?>
        </span>
    </td>
    <td class="amount"><?= number_format($r['TotalAmount'],2) ?></td>
    <td class="amount text-success"><?= number_format($r['NetPayable'],2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot>
<tr>
    <td colspan="8" class="text-end">Selected Net Total</td>
    <td class="text-success amount" id="selectedTotal">0.00</td>
</tr>
</tfoot>

</table>
</div>

</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(function(){

    let table = $('#billTable').DataTable({
        pageLength:10,
        order:[[1,'desc']]
    });

    function updateTotal(){
    let total = 0;

    table.rows().nodes().to$().find('.bill-check:checked').each(function(){
        total += parseFloat($(this).closest('tr').data('amount')) || 0;
    });

    $('#selectedTotal').text(total.toFixed(2));
}


   $('#selectAll').on('change', function () {
    let checked = this.checked;

    table.rows({ search: 'applied' }).nodes().to$()
        .find('.bill-check')
        .prop('checked', checked);

    updateTotal();
});


    $(document).on('change','.bill-check',updateTotal);

    $('#createBatch').click(function(){

        let bills = $('.bill-check:checked').map(function(){
            return this.value;
        }).get();

        if(bills.length === 0){
            Swal.fire('Warning','Please select at least one bill','warning');
            return;
        }

        Swal.fire({
    title: 'Create Batch',
    html: `
        <input id="batchNo" class="swal2-input" placeholder="Batch Number">
        <input id="voucherNo" class="swal2-input" placeholder="Voucher Number">
        <p class="mt-2 text-muted">
            Selected Bills: <b>${bills.length}</b><br>
            Total Amount: <b>₹ ${$('#selectedTotal').text()}</b>
        </p>
    `,
    showCancelButton: true,
    confirmButtonText: 'Save Batch',
    preConfirm: () => {
        return {
            batchNo: $('#batchNo').val(),
            voucherNo: $('#voucherNo').val()
        };
    }
}).then(res => {

    if (!res.isConfirmed) return;

    if (!res.value.batchNo || !res.value.voucherNo) {
        Swal.fire('Error', 'Batch No and Voucher No are required', 'error');
        return;
    }

    $.post('transaction_batch_ajax.php', {
        bills: bills,
        batch_no: res.value.batchNo,
        voucher_no: res.value.voucherNo
    }, function (resp) {
        if (resp.status === 'success') {
            Swal.fire('Success', resp.message, 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', resp.message, 'error');
        }
    }, 'json');
});
    });

});
</script>

</body>
</html>

<?php
include '../config/db.php';
include '../includes/auth.php';

// Fetch bills with aggregated invoice totals
$rows = $conn->query("
    SELECT 
        bi.Id,
        COALESCE(bi.BillNumber,'— Draft —') AS BillNumber,
        bi.BillReceivedDate,
        im.ReceivedFromSection,
        e.EmployeeName AS AllotedName,
        b.Status,
        SUM(im.Amount) AS TotalAmount,
        SUM(im.GSTAmount) AS TotalGST,
        SUM(im.ITAmount) AS TotalIT,
        SUM(im.TDSGSTAmount + im.TDSITAmount) AS TotalTDS,
        SUM(im.TotalAmount) AS GrossTotal,
        SUM(im.NetPayable) AS NetTotal
    FROM bill_initial_entry bi
    LEFT JOIN bill_entry b ON bi.Id = b.BillInitialId
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    LEFT JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    LEFT JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY 
    bi.Id, b.Status, bi.BillNumber,bi.CreatedDate, bi.BillReceivedDate, im.ReceivedFromSection, e.EmployeeName
ORDER BY bi.CreatedDate DESC
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
body{background:#f8f9fa;}
.page-content{margin-left:240px;padding:50px 30px;}
</style>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <div class="card shadow-sm p-4">
        <h4 class="text-primary fw-bold mb-4">
            📄 All Bills with Details
        </h4>

        <table id="billTable" class="table table-striped table-bordered table-hover table-sm">
            <thead class="table-primary text-center">
                <tr>
                    <th>#</th>
                    <th>Bill No</th>
                    <th>Bill Type</th>
                    <th>Received Date</th>
                    <th>From Section</th>
                    <th>Alloted To</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Total GST</th>
                    <th>Total IT</th>
                    <th>Total TDS</th>
                    <th>Gross Total</th>
                    <th>Net Payable</th>
                    <th>History / Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($rows as $r): ?>
                <tr class="text-center">
                    <td><?= $r['Id'] ?></td>
                    <td><?= htmlspecialchars($r['BillNumber']) ?></td>
                    <td><?= $r['BillReceivedDate'] ? date('d-m-Y', strtotime($r['BillReceivedDate'])) : '-' ?></td>
                    <td><?= htmlspecialchars($r['ReceivedFromSection']) ?></td>
                    <td><?= htmlspecialchars($r['AllotedName'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['Status'] ?? 'Draft') ?></td>

                    <td>₹ <?= number_format($r['TotalAmount'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['TotalGST'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['TotalIT'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['TotalTDS'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['GrossTotal'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['NetTotal'] ?? 0,2) ?></td>

                    <td>
                        <?php if ($r['Status'] === NULL): ?>
                            <a href="bill_entry_add.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-success">
                                ▶ Proceed
                            </a>
                        <?php else: ?>
                            <a href="bill_history.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-info">
                                📜 History
                            </a>
                        <?php endif; ?>
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

<script src="../js/datatables/dataTables.buttons.min.js"></script>
<script src="../js/datatables/jszip.min.js"></script>
<script src="../js/datatables/pdfmake.min.js"></script>
<script src="../js/datatables/vfs_fonts.js"></script>
<script src="../js/datatables/buttons.html5.min.js"></script>
<script src="../js/datatables/buttons.print.min.js"></script>

<script>
$(document).ready(function(){
    $('#billTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend:'excel', text:'📥 Excel', className:'btn btn-success btn-sm' },
            { extend:'pdf', text:'📄 PDF', className:'btn btn-danger btn-sm' },
            { extend:'print', text:'🖨 Print', className:'btn btn-secondary btn-sm' }
        ],
        pageLength: 15,
        order:[[0,'desc']]
    });
});
</script>

</body>
</html>

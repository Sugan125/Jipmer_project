<?php
include '../config/db.php';
include '../includes/auth.php';

// Fetch bills with aggregated invoice totals
$rows = $conn->query("
  WITH InvoiceAgg AS (
    SELECT
        bim.BillInitialId,

        SUM(im.Amount) AS TotalAmount,
        SUM(im.GSTAmount) AS TotalGST,
        SUM(im.ITAmount) AS TotalIT,
        SUM(im.TDSGSTAmount + im.TDSITAmount) AS TotalTDS,
        SUM(im.TotalAmount) AS GrossTotal,
        SUM(im.NetPayable) AS NetTotal,

        MAX(im.ReceivedFromSection) AS ReceivedFromSection,
        MAX(im.BillTypeId) AS BillTypeId   -- ✅ BillType from invoice

    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY bim.BillInitialId
),
POAgg AS (
    SELECT
        bim.BillInitialId,

        SUM(DISTINCT pm.POAmount) AS POTotal,
        SUM(DISTINCT pm.POAmount * pm.POGSTPercent / 100.0) AS POGSTTotal,
        SUM(DISTINCT pm.POAmount * pm.POITPercent / 100.0)  AS POITTotal,

        SUM(DISTINCT
            pm.POAmount
            + (pm.POAmount * pm.POGSTPercent / 100.0)
            + (pm.POAmount * pm.POITPercent / 100.0)
        ) AS POTotalGross

    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    JOIN po_master pm ON pm.Id = im.POId
    GROUP BY bim.BillInitialId
)

SELECT
    bi.Id,
    COALESCE(bi.BillNumber,'— Draft —') AS BillNumber,
    COALESCE(btm.BillType,'Draft') AS BillType,
    bi.BillReceivedDate,

    ia.ReceivedFromSection,
    e.EmployeeName AS AllotedName,
    b.Status,

    ia.TotalAmount,
    ia.TotalGST,
    ia.TotalIT,
    ia.TotalTDS,
    ia.GrossTotal,
    ia.NetTotal,

    pa.POTotal,
    pa.POGSTTotal,
    pa.POITTotal,
    pa.POTotalGross,
    pa.POTotalGross AS POTotalNet   -- no TDS yet

FROM bill_initial_entry bi
LEFT JOIN bill_entry b 
    ON b.BillInitialId = bi.Id

LEFT JOIN employee_master e 
    ON e.Id = b.AllotedDealingAsst

LEFT JOIN InvoiceAgg ia 
    ON ia.BillInitialId = bi.Id

LEFT JOIN bill_type_master btm 
    ON btm.Id = ia.BillTypeId   -- ✅ correct join

LEFT JOIN POAgg pa 
    ON pa.BillInitialId = bi.Id

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
                    <th>PO Amount</th>
                    <th>PO GST</th>
                    <th>PO IT</th>
                    <th>PO Gross</th>
                    <th>PO Net Payable</th>
                    <th>History / Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($rows as $r): ?>
                <tr class="text-center">
                    <td><?= $r['Id'] ?></td>
                    <td><?= htmlspecialchars($r['BillNumber']) ?></td>
                    <td><?= htmlspecialchars($r['BillType']) ?></td>
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

                    <td>₹ <?= number_format($r['POTotal'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['POGSTTotal'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['POITTotal'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['POTotalGross'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['POTotalNet'] ?? 0,2) ?></td>

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

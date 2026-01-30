<?php
include '../config/db.php';
include '../includes/auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {

$rows = $conn->query("
WITH BillLatest AS (
    SELECT 
        be.BillInitialId,
        be.Status,
        be.AllotedDealingAsst,
        be.TokenNo,
        ROW_NUMBER() OVER (PARTITION BY be.BillInitialId ORDER BY be.Id DESC) AS rn
    FROM bill_entry be
),
InvoiceAgg AS (
    SELECT
        bim.BillInitialId,

        COUNT(DISTINCT im.Id)      AS InvoiceCount,

        SUM(ISNULL(im.Amount,0))       AS TotalAmount,
        SUM(ISNULL(im.TDSGSTAmount,0)) AS TotalGST,
        SUM(ISNULL(im.TDSITAmount,0))  AS TotalIT,
        SUM(ISNULL(im.TDS,0))          AS TotalTDS,
        SUM(ISNULL(im.TotalAmount,0))  AS GrossTotal,
        SUM(ISNULL(im.NetPayable,0))   AS NetTotal,

        MAX(im.ReceivedFromSection) AS ReceivedFromSection,
        MAX(im.BillTypeId)          AS BillTypeId

    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY bim.BillInitialId
),
POAgg AS (
    SELECT
        bim.BillInitialId,
        SUM(DISTINCT ISNULL(pm.POAmount,0))    AS POTotal,
        SUM(DISTINCT ISNULL(pm.POGSTAmount,0)) AS POGSTTotal,
        SUM(DISTINCT ISNULL(pm.POITAmount,0))  AS POITTotal,
        SUM(DISTINCT ISNULL(pm.PONetAmount,0)) AS POTotalNet,
        SUM(DISTINCT (ISNULL(pm.POAmount,0) + ISNULL(pm.POGSTAmount,0) + ISNULL(pm.POITAmount,0))) AS POTotalGross
    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    LEFT JOIN po_master pm ON pm.Id = im.POId
    GROUP BY bim.BillInitialId
)

SELECT
    bi.Id,
    COALESCE(bi.BillNumber,'Draft') AS BillNumber,
    COALESCE(btm.BillType,'Draft') AS BillType,
    bi.BillReceivedDate,

    ia.InvoiceCount,
    ia.ReceivedFromSection,

    emp.EmployeeName AS AllotedName,
    bl.Status,
    bl.TokenNo,

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
    pa.POTotalNet,

    /* -------- Invoice Numbers list -------- */
    STUFF((
        SELECT ', ' + im2.InvoiceNo + ' (' + CONVERT(varchar(10), im2.InvoiceDate, 105) + ')'
        FROM bill_invoice_map bim2
        JOIN invoice_master im2 ON im2.Id = bim2.InvoiceId
        WHERE bim2.BillInitialId = bi.Id
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS InvoiceNos,

    /* -------- Vendors list -------- */
    STUFF((
        SELECT DISTINCT ', ' + ISNULL(im2.VendorName,'')
        FROM bill_invoice_map bim2
        JOIN invoice_master im2 ON im2.Id = bim2.InvoiceId
        WHERE bim2.BillInitialId = bi.Id
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS Vendors,

    /* -------- PO Nos list -------- */
    STUFF((
        SELECT DISTINCT ', ' + ISNULL(pm2.POOrderNo,'')
        FROM bill_invoice_map bim2
        JOIN invoice_master im2 ON im2.Id = bim2.InvoiceId
        LEFT JOIN po_master pm2 ON pm2.Id = im2.POId
        WHERE bim2.BillInitialId = bi.Id
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS PONos,

    /* -------- Sanction Nos list -------- */
    STUFF((
        SELECT DISTINCT ', ' + ISNULL(so2.SanctionOrderNo,'')
        FROM bill_invoice_map bim2
        JOIN invoice_master im2 ON im2.Id = bim2.InvoiceId
        LEFT JOIN invoice_sanction_map ism2 ON ism2.InvoiceId = im2.Id
        LEFT JOIN sanction_order_master so2 ON so2.Id = ism2.SanctionId
        WHERE bim2.BillInitialId = bi.Id
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS SanctionNos,

    /* -------- Departments list -------- */
    STUFF((
        SELECT DISTINCT ', ' + ISNULL(d2.DeptName,'')
        FROM bill_invoice_map bim2
        JOIN invoice_master im2 ON im2.Id = bim2.InvoiceId
        LEFT JOIN dept_master d2 ON d2.Id = im2.DeptId
        WHERE bim2.BillInitialId = bi.Id
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS DeptNames,

    /* -------- HOA list -------- */
    STUFF((
        SELECT DISTINCT ', ' + (ISNULL(h2.DetailsHeadCode,'') + ' - ' + ISNULL(h2.DetailsHeadName,''))
        FROM bill_invoice_map bim2
        JOIN invoice_master im2 ON im2.Id = bim2.InvoiceId
        LEFT JOIN hoa_master h2 ON h2.HOAId = im2.HOAId
        WHERE bim2.BillInitialId = bi.Id
        FOR XML PATH(''), TYPE
    ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS HOAs

FROM bill_initial_entry bi
LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = bi.Id
LEFT JOIN bill_type_master btm ON btm.Id = ia.BillTypeId

LEFT JOIN BillLatest bl ON bl.BillInitialId = bi.Id AND bl.rn = 1
LEFT JOIN employee_master emp ON emp.Id = bl.AllotedDealingAsst

LEFT JOIN POAgg pa ON pa.BillInitialId = bi.Id

ORDER BY bi.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<h3 style='color:red'>SQL Error:</h3><pre>".$e->getMessage()."</pre>");
}

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
.bill-table-wrapper{
    overflow-x: auto;
    white-space: nowrap;
}

/* Optional: nicer scrollbar */
.bill-table-wrapper::-webkit-scrollbar {
    height: 8px;
}
.bill-table-wrapper::-webkit-scrollbar-thumb {
    background: #adb5bd;
    border-radius: 6px;
}
.bill-table-wrapper::-webkit-scrollbar-track {
    background: #f1f3f5;
}
#billTable thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #0d6efd;
    color: #fff;
}
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

       <div class="table-responsive bill-table-wrapper">
    <table id="billTable" class="table table-striped table-bordered table-hover table-sm w-100">
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
                    <!-- <th>PO Amount</th>
                    <th>PO GST</th>
                    <th>PO IT</th>
                    <th>PO Gross</th>
                    <th>PO Net Payable</th>
                    <th>Invoices</th>
                    <th>Vendors</th>
                    <th>PO Nos</th>
                    <th>Sanctions</th>
                    <th>Departments</th>
                    <th>HOA</th> -->
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
                    <td><?= htmlspecialchars($r['ReceivedFromSection'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['AllotedName'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['Status'] ?? 'Draft') ?></td>

                    <td>₹ <?= number_format($r['TotalAmount'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['TotalGST'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['TotalIT'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['TotalTDS'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['GrossTotal'] ?? 0,2) ?></td>
                    <td>₹ <?= number_format($r['NetTotal'] ?? 0,2) ?></td>

                    <!-- <td>₹ <= number_format($r['POTotal'] ?? 0,2) ?></td>
                    <td>₹ <= number_format($r['POGSTTotal'] ?? 0,2) ?></td>
                    <td>₹ <= number_format($r['POITTotal'] ?? 0,2) ?></td>
                    <td>₹ <= number_format($r['POTotalGross'] ?? 0,2) ?></td>
                    <td>₹ <= number_format($r['POTotalNet'] ?? 0,2) ?></td>

                    <td class="text-start"><= htmlspecialchars($r['InvoiceNos'] ?? '-') ?></td>
                    <td class="text-start"><= htmlspecialchars($r['Vendors'] ?? '-') ?></td>
                    <td class="text-start"><= htmlspecialchars($r['PONos'] ?? '-') ?></td>
                    <td class="text-start"><= htmlspecialchars($r['SanctionNos'] ?? '-') ?></td>
                    <td class="text-start"><= htmlspecialchars($r['DeptNames'] ?? '-') ?></td>
                    <td class="text-start">?= htmlspecialchars($r['HOAs'] ?? '-') ?></td> -->

                    <td>
                        <?php if (($r['Status'] ?? NULL) === NULL): ?>
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
    scrollX: true,
    scrollCollapse: true,
    autoWidth: false,
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

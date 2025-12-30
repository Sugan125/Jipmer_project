<?php
include '../config/db.php';
include '../includes/auth.php';

// Authorization check (commented out for now)
// $page = basename($_SERVER['PHP_SELF']);
// $stmt = $conn->prepare("
//     SELECT COUNT(*)
//     FROM menu_master m
//     JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
//     WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
// ");
// $stmt->execute([$_SESSION['role'], "%$page%"]);

// Fetch bills with totals from invoices
$rows = $conn->query("
    SELECT 
        bi.Id,
        b.Status,
        bi.CreatedDate,
        COALESCE(bi.BillNumber, '— Draft —') AS BillNumber,
        bi.BillReceivedDate,
        bi.ReceivedFromSection,
        e.EmployeeName AS AllotedName,
        COALESCE(btm.BillType, 'Draft') AS BillType,
        -- Aggregate totals from invoices
        SUM(im.Amount) AS TotalAmount,
        SUM(im.GSTAmount) AS TotalGST,
        SUM(im.ITAmount) AS TotalIT,
        SUM(im.TDS) AS TotalTDS,
        SUM(im.TotalAmount) AS GrossTotal,
        SUM((im.TotalAmount + COALESCE(im.GSTAmount,0) - COALESCE(im.ITAmount,0) - COALESCE(im.TDS,0))) AS NetTotal
    FROM bill_initial_entry bi
    LEFT JOIN bill_entry b ON bi.Id = b.BillInitialId
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    LEFT JOIN bill_type_master btm ON btm.Id = bi.BillTypeId
    LEFT JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
    LEFT JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY 
    bi.Id, b.Status, bi.CreatedDate, bi.BillNumber, bi.BillReceivedDate, 
    bi.ReceivedFromSection, e.EmployeeName, btm.BillType, b.CreatedDate
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Bills</title>

<!-- Bootstrap -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">

<!-- DataTables CSS -->
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="../css/style.css">

</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container" style="margin-top:80px;">
    <div class="card shadow-sm p-4">
        <h4 class="text-primary fw-bold mb-4">
            📄 All Bill Entries
        </h4>

        <table id="billTable" class="table table-striped table-bordered table-hover">
            <thead class="table-primary">
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
                    <th>Gross</th>
                    <th>Net</th>
                    <th>History</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= $r['Id'] ?></td>
                    <td><?= htmlspecialchars($r['BillNumber']) ?></td>
                    <td><?= htmlspecialchars($r['BillType']) ?></td>
                    <td>
                        <?= !empty($r['BillReceivedDate']) 
                            ? date('d-m-Y', strtotime($r['BillReceivedDate'])) 
                            : '-' ?>
                    </td>
                    <td><?= htmlspecialchars($r['ReceivedFromSection']) ?></td>
                    <td><?= htmlspecialchars($r['AllotedName'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['Status'] === NULL ? 'Draft' : $r['Status']) ?></td>

                    <td><?= number_format($r['TotalAmount'] ?? 0, 2) ?></td>
                    <td><?= number_format($r['TotalGST'] ?? 0, 2) ?></td>
                    <td><?= number_format($r['TotalIT'] ?? 0, 2) ?></td>
                    <td><?= number_format($r['TotalTDS'] ?? 0, 2) ?></td>
                    <td><?= number_format($r['GrossTotal'] ?? 0, 2) ?></td>
                    <td><?= number_format($r['NetTotal'] ?? 0, 2) ?></td>

                    <td class="text-center">
                        <?php if ($r['Status'] === NULL): ?>
                            <a href="bill_entry_add.php?id=<?= $r['Id'] ?>" 
                               class="btn btn-sm btn-success">
                               ▶ Proceed to Bill Entry
                            </a>
                        <?php else: ?>
                            <a href="bill_history.php?id=<?= $r['Id'] ?>" 
                               class="btn btn-sm btn-info">
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

<!-- Scripts -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Export Buttons -->
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
            { extend: 'excel', text: '📥 Export Excel', className:'btn btn-success btn-sm' },
            { extend: 'pdf', text: '📄 Export PDF', className:'btn btn-danger btn-sm' },
            { extend: 'print', text: '🖨 Print', className:'btn btn-secondary btn-sm' }
        ],
        pageLength: 10,
        order: [[0, 'desc']]
    });
});
</script>

</body>
</html>

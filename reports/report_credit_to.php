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
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}
 // Admin

// Fetch bills credited to GIA or Income Tax
$rows = $conn->query("
    SELECT 
        b.Id,
        b.BillNo,
        b.BillReceivedDate,
        b.TokenNo,
        b.Status,
        b.ReceivedFromSection,
        b.SectionDAName,
        e.EmployeeName AS AllotedName,
        t.TransactionNo,
        t.BatchNo,
        p.TotalAmount,
        c.CreditName AS CreditTo
    FROM bill_entry b
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    LEFT JOIN bill_transactions t ON t.BillId = b.Id
    LEFT JOIN bill_process p ON p.BillId = b.Id
    LEFT JOIN account_credit_master c ON c.Id = b.CreditToId
    WHERE c.CreditName IN ('GIA', 'Income Tax')
    ORDER BY b.BillReceivedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Credit To Report</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../js/datatables/buttons.bootstrap5.min.css">


<style>
/* Unique report style */
.report-card { 
    background: #f8f9fa; 
    border-radius: 12px; 
    padding: 30px; 
    box-shadow: 0 4px 10px rgba(0,0,0,0.08); 
}
.report-header h3 {
    font-weight: 700;
    color: #0d6efd;
}
.dataTables_wrapper .dt-buttons .btn {
    border-radius: 6px; 
    margin-right: 5px;
}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-5">
    <div class="report-card">
        <div class="report-header mb-4">
            <h3>Credit To Report - GIA & Income Tax</h3>
            <p class="text-muted">List of all bills credited to GIA or Income Tax with export options</p>
        </div>

        <div class="table-responsive">
            <table id="creditReportTable" class="table table-striped table-bordered table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Bill No</th>
                        <th>Received Date</th>
                        <th>From Section</th>
                        <th>DA Name</th>
                        <th>Token No</th>
                        <th>Total Amount</th>
                        <th>Credit To</th>
                        <th>Alloted To</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?= $r['Id'] ?></td>
                        <td><?= htmlspecialchars($r['BillNo']) ?></td>
                        <td><?= date('d-m-Y', strtotime($r['BillReceivedDate'])) ?></td>
                        <td><?= htmlspecialchars($r['ReceivedFromSection']) ?></td>
                        <td><?= htmlspecialchars($r['SectionDAName']) ?></td>
                        <td><?= htmlspecialchars($r['TokenNo']) ?></td>
                        <td><?= number_format($r['TotalAmount'],2) ?></td>
                        <td><?= htmlspecialchars($r['CreditTo']) ?></td>
                        <td><?= htmlspecialchars($r['AllotedName']) ?></td>
                        <td><?= htmlspecialchars($r['Status']) ?></td>
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
<script src="../js/datatables/buttons.bootstrap5.min.js"></script>

<script src="../js/datatables/jszip.min.js"></script>
<script src="../js/datatables/pdfmake.min.js"></script>
<script src="../js/datatables/vfs_fonts.js"></script>

<script src="../js/datatables/buttons.html5.min.js"></script>
<script src="../js/datatables/buttons.print.min.js"></script>


<script>
$(document).ready(function(){
    $('#creditReportTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: '📥 Export Excel', className:'btn btn-success btn-sm' },
            { extend: 'pdf', text: '📄 Export PDF', className:'btn btn-danger btn-sm' },
            { extend: 'print', text: '🖨 Print', className:'btn btn-secondary btn-sm' }
        ],
        pageLength: 15,
        order: [[2,'desc']]
    });
});
</script>

</body>
</html>

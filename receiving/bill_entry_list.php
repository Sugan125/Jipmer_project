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


// Fetch bills
$rows = $conn->query("
    SELECT b.*, e.EmployeeName AS AllotedName , btm.BillType
    FROM bill_entry b
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    left join bill_type_master btm on  btm.Id = b.BillTypeId
    ORDER BY b.CreatedDate DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html>
<head>
    <title>All Bills</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

    <!-- Custom Style -->
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
                    <th>Received</th>
                    <th>From Section</th>
                    <th>Alloted To</th>
                    <th>Status</th>
                    <th>History</th>
                    <!-- <th class="text-center">Actions</th> -->
                </tr>
            </thead>

            <tbody>
                <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= $r['Id'] ?></td>
                    <td><?= htmlspecialchars($r['BillNo']) ?></td>
                    <td><?= $r['BillType'] ?>
                    <td><?= $r['BillReceivedDate'] ?></td>
                    <td><?= htmlspecialchars($r['ReceivedFromSection']) ?></td>
                    <td><?= htmlspecialchars($r['AllotedName']) ?></td>
                    <td><?= htmlspecialchars($r['Status']) ?></td>
                    <td class="text-center">
                        <a href="bill_history.php?id=<?= $r['Id'] ?>" 
                        class="btn btn-sm btn-info">
                        📜 History
                        </a>
                    </td>

                    <!-- <td class="text-center">
                        <a class="btn btn-sm btn-primary" 
                           href="../processing/process_bill.php?bill=<= $r['Id'] ?>">
                           Process
                        </a>

                        <a class="btn btn-sm btn-info text-white" 
                           href="bill_view.php?id=<= $r['Id'] ?>">
                           View
                        </a>
                    </td> -->
                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<!-- jQuery -->
<script src="../js/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>

<!-- Export Buttons -->
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

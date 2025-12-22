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


// Fetch returned bills
$rows = $conn->query("
    SELECT b.*, e.EmployeeName AS AllotedName 
    FROM bill_entry b
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    WHERE b.Status = 'Returned'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bill Type Master</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
  
</head>

<body>

<?php
$topbar = realpath(__DIR__ . '/../../layout/topbar.php')
       ?: realpath(__DIR__ . '/../layout/topbar.php')
       ?: realpath(__DIR__ . '/../../../layout/topbar.php')
       ?: realpath(__DIR__ . '/../../includes/topbar.php')
       ?: realpath(__DIR__ . '/../../includes/layout/topbar.php');

$sidebar = realpath(__DIR__ . '/../../layout/sidebar.php')
        ?: realpath(__DIR__ . '/../layout/sidebar.php')
        ?: realpath(__DIR__ . '/../../../layout/sidebar.php')
        ?: realpath(__DIR__ . '/../../includes/sidebar.php')
        ?: realpath(__DIR__ . '/../../includes/layout/sidebar.php');

if (!$topbar || !$sidebar) {
    die('Layout files not found. Please check folder structure.');
}

require $topbar;
require $sidebar;


?>

<div class="container mt-5">

    <div class="page-card">

        <!-- HEADER -->
        <div class="card shadow-sm p-4">
        <h4 class="text-danger fw-bold mb-4">📄 Returned Bills</h4>
       

        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                     <th>Bill No</th>
                    <th>Token No</th>
                    <th>Received</th>
                    <th>From Section</th>
                    <th>Section Dealing Assistant</th>
                    <th>Alloted To</th>
                    <th>Alloted Date</th>
                    <th>Reason for Returning Bill</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($rows) > 0): ?>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?= $r['Id'] ?></td>
                <td><?= htmlspecialchars($r['BillNo']) ?></td>
                <td><?= htmlspecialchars($r['TokenNo']) ?></td>
                <td><?= $r['BillReceivedDate'] ?></td>
                <td><?= htmlspecialchars($r['ReceivedFromSection']) ?></td>
                <td><?= htmlspecialchars($r['SectionDAName']) ?></td>
                <td><?= htmlspecialchars($r['AllotedName']) ?></td>
                <td><?= htmlspecialchars($r['AllotedDate']) ?></td>
                <td><?= htmlspecialchars($r['Remarks']) ?></td>
                <td>
                        <?php if ($r['concerned_reply'] == 'Y'): ?>
                            <span class="text-success">Reply Submitted</span>
                        <?php else: ?>
                           <form action="returned_bill_reply.php" method="post" style="display:inline;">
                            <input type="hidden" name="bill_id" value="<?= $r['Id'] ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Reply
                            </button>
                        </form>
                        <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="10" class="text-center text-danger">No records found</td>
            </tr>
        <?php endif; ?>
        </tbody>

                </table>
 </div>
    </div>
</div>
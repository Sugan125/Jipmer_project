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


// Fetch returned bills
$rows = $conn->query("
    WITH InvoiceAgg AS (
        SELECT
            bim.BillInitialId,
            MAX(im.SectionDAName) AS SectionDAName,
            MAX(im.ReceivedFromSection) AS ReceivedFromSection
        FROM bill_invoice_map bim
        JOIN invoice_master im ON im.Id = bim.InvoiceId
        GROUP BY bim.BillInitialId
    )
    SELECT
        b.*,
        cs.ReplyText,
        e.EmployeeName AS AllotedName,
        bi.BillNumber,
        bi.BillReceivedDate,
        ia.SectionDAName,
        ia.ReceivedFromSection
    FROM bill_entry b
    JOIN bill_initial_entry bi 
        ON bi.Id = b.BillInitialId
    LEFT JOIN InvoiceAgg ia 
        ON ia.BillInitialId = bi.Id
    LEFT JOIN employee_master e 
        ON b.AllotedDealingAsst = e.Id
    LEFT JOIN concerned_section_reply cs 
        ON cs.BillId = b.Id
    WHERE b.Status = 'Return'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Returned Bills</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm p-4">
        <h4 class="text-danger fw-bold mb-4">📄 Returned Bills</h4>

        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                     <th>Bill No</th>
                    <th>Token No</th>
                    <th>Received</th>
                    <th>Alloted To</th>
                    <th>Alloted Date</th>
                    <th>Reason for Returning Bill</th>
                    <th>Reply of Concerned Section</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php if (count($rows) > 0): ?>
    <?php foreach($rows as $r): ?>
    <tr>
        <td><?= $r['Id'] ?></td>
        <td><?= htmlspecialchars($r['BillNumber']) ?></td>
        <td><?= htmlspecialchars($r['TokenNo']) ?></td>
        <td><?= $r['BillReceivedDate'] ?></td>
        <td><?= htmlspecialchars($r['AllotedName']) ?></td>
        <td><?= htmlspecialchars($r['AllotedDate']) ?></td>
        <td><?= htmlspecialchars($r['Remarks']) ?></td>
        <td><?= htmlspecialchars($r['ReplyText']) ?></td>
       <td>
<?php if ($r['reviewed'] == 'Y'): ?>
    <a href="returned_bill_resubmit.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-primary">
        Resubmit
    </a>
<?php elseif ($r['concerned_reply'] == 'Y'): ?>
    <a href="returned_bill_preview.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-warning">
        Preview & Review
    </a>
<?php else: ?>
    <button class="btn btn-sm btn-secondary" disabled>
        Awaiting Reply
    </button>
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
</body>
</html>

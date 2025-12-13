<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(1);

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
    <title>Returned Bills</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">
<?php include '../header/header_receiving.php'; ?>

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
                    <th>From Section</th>
                    <th>Section Dealing Assistant</th>
                    <th>Alloted To</th>
                    <th>Alloted Date</th>
                    <th>Remarks</th>
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
            <a href="returned_bill_resubmit.php?id=<?= $r['Id'] ?>" class="btn btn-sm btn-primary">
                Resubmit
            </a>
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

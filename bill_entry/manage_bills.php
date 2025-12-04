<?php
include("../config/db.php");
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) { header("Location: login.php"); exit; }

// Fetch bills with employee name
$bills = $conn->query("
    SELECT b.Id, b.BillNo, b.FullHOA, b.Status, b.Remarks, e.EmployeeName, b.CreatedBy
    FROM bill_entry b
    LEFT JOIN employee_master e ON b.AllotedTo = e.Id
    ORDER BY b.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Bills</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../header/header.php'; ?>

<div class="container mt-5">
    <h3>All Bills</h3>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Bill Number</th>
                    <th>Full Head of Account</th>
                    <th>Alloted To</th>
                    <th>Status</th>
                    <th>Pass/Return By</th>
                    <th>Remarks for Return</th>
                </tr>
            </thead>
            <tbody>
                 <?php $sn = 1; // serial number ?>
                <?php foreach($bills as $b): ?>
                <tr>
                    <td><?= $sn++; ?></td> <!-- Increment serial number -->
                    <td><?= htmlspecialchars($b['BillNo']) ?></td>
                    <td><?= htmlspecialchars($b['FullHOA']) ?></td>
                    <td><?= htmlspecialchars($b['EmployeeName'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($b['Status']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($b['CreatedBy'] ?? '-')) ?></td>
                    <td><?= ucfirst(htmlspecialchars($b['Remarks'] ?? '-')) ?></td>

                    
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

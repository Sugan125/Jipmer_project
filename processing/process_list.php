<?php
include '../config/db.php';
include '../includes/auth.php';

$page = basename($_SERVER['PHP_SELF']);

// Authorization check
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) die("Unauthorized Access");

$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = ($_SESSION['role'] == '5'); // your logic

$sql = "
WITH LatestEntry AS (
    SELECT
        be.*,
        ROW_NUMBER() OVER (PARTITION BY be.BillInitialId ORDER BY be.Id DESC) AS rn
    FROM bill_entry be
),
InvoiceAgg AS (
    SELECT
        bim.BillInitialId,
        COUNT(*) AS InvoiceCount,
        STRING_AGG(im.InvoiceNo, ', ') AS InvoiceNos,
        STRING_AGG(im.VendorName, ', ') AS Vendors,
        SUM(ISNULL(im.NetPayable,0)) AS TotalNetPayable,
        MAX(im.InvoiceDate) AS LastInvoiceDate
    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY bim.BillInitialId
)
SELECT
    le.Id AS BillEntryId,
    le.BillInitialId,
    le.Status,
    le.Remarks,
    le.AllotedDealingAsst,

    bi.BillNumber,
    bi.BillReceivedDate,

    emp.EmployeeName AS AllotedName,

    ia.InvoiceCount,
    ia.InvoiceNos,
    ia.Vendors,
    ia.TotalNetPayable,
    ia.LastInvoiceDate

FROM LatestEntry le
JOIN bill_initial_entry bi ON bi.Id = le.BillInitialId
LEFT JOIN employee_master emp ON emp.Id = le.AllotedDealingAsst
LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = le.BillInitialId

WHERE le.rn = 1
  AND le.Status IN ('Pending','Return')
";

if(!$isAdmin){
    $sql .= " AND le.AllotedDealingAsst = :user_id ";
}

$sql .= " ORDER BY le.CreatedDate DESC;";

$stmt = $conn->prepare($sql);
if(!$isAdmin){
    $stmt->execute(['user_id' => $userId]);
} else {
    $stmt->execute();
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s); }
function nf($v){ return number_format((float)($v ?? 0), 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bills to Process</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<style>
body { margin: 0; min-height: 100vh; background-color: #f8f9fa; }
.page-content { margin-left: 240px; padding: 50px 20px 20px 20px; }
.table-responsive { margin: auto; }
.small-muted{font-size:12px;color:#6c757d;}
</style>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <h3 class="mb-4 text-center">Bills to Process</h3>

    <div class="table-responsive shadow rounded bg-white p-3">
        <table id="billsTable" class="table table-striped table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Entry ID</th>
                    <th>Bill No</th>
                    <th>Received</th>
                    <th>Invoices</th>
                    <th>Vendors</th>
                    <th>Last Invoice Dt</th>
                    <th class="text-end">Total Net</th>
                    <th>Alloted To</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= (int)$r['BillEntryId'] ?></td>

                    <td class="fw-semibold"><?= h($r['BillNumber'] ?? '-') ?></td>

                    <td><?= !empty($r['BillReceivedDate']) ? date('d-m-Y', strtotime($r['BillReceivedDate'])) : '-' ?></td>

                    <td>
                        <div class="fw-bold"><?= (int)($r['InvoiceCount'] ?? 0) ?> invoice(s)</div>
                        <div class="small-muted"><?= h($r['InvoiceNos'] ?? '-') ?></div>
                    </td>

                    <td style="min-width:220px;">
                        <?= h($r['Vendors'] ?? '-') ?>
                    </td>

                    <td><?= !empty($r['LastInvoiceDate']) ? date('d-m-Y', strtotime($r['LastInvoiceDate'])) : '-' ?></td>

                    <td class="text-end fw-bold text-success"><?= nf($r['TotalNetPayable'] ?? 0) ?></td>

                    <td><?= h($r['AllotedName'] ?? '-') ?></td>

                    <td>
                        <?php if(($r['Status'] ?? '') == 'Pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif(($r['Status'] ?? '') == 'Return'): ?>
                            <span class="badge bg-danger">Return</span>
                        <?php else: ?>
                            <span class="badge bg-info"><?= h($r['Status'] ?? '-') ?></span>
                        <?php endif; ?>
                    </td>

                    <td><?= h($r['Remarks'] ?? '-') ?></td>

                    <td class="text-nowrap">
                        <!-- Redirect to your detailed page -->
                        <a href="../receiving/bill_history.php?id=<?= (int)$r['BillInitialId'] ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>

                        <button
                            class="btn btn-sm btn-primary process-btn"
                            data-id="<?= (int)$r['BillInitialId'] ?>"
                            <?= (($r['Status'] ?? '') == 'Return') ? 'disabled' : '' ?>
                        >
                            <i class="fas fa-play-circle"></i> Process
                        </button>
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
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(function () {
    $('#billsTable').DataTable({
        lengthMenu: [5, 10, 25, 50],
        pageLength: 10,
        ordering: true,
        columnDefs: [{ orderable: false, targets: 10 }]
    });

    $('.process-btn').on('click', function(){
        var billId = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to process this bill!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<i class="fas fa-check-circle"></i> Yes, process it'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = $('<form action="process_bill.php" method="POST">' +
                    '<input type="hidden" name="bill_id" value="' + billId + '">' +
                    '</form>');
                $('body').append(form);
                form.submit();
            }
        });
    });
});
</script>

</body>
</html>

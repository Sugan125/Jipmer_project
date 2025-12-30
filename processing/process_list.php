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


// Fetch bills with status Pending or Returned
$userId = $_SESSION['user_id'];

if($_SESSION['role'] == '5'){
   
    $stmt = $conn->prepare("
    SELECT b.*,bn.BillNumber, Bn.BillReceivedDate, e.EmployeeName AS AllotedName 
    FROM bill_entry b
    left join bill_initial_entry bn on bn.Id = b.BillInitialId
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    WHERE b.Status IN ('Pending','Returned') 
    ORDER BY b.CreatedDate DESC
");
$stmt->execute();

}
else{
 
$stmt = $conn->prepare("
    SELECT b.*,bn.BillNumber, Bn.BillReceivedDate, e.EmployeeName AS AllotedName 
    FROM bill_entry b
    left join bill_initial_entry bn on bn.Id = b.BillInitialId
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    WHERE b.Status IN ('Pending','Returned') 
      AND b.AllotedDealingAsst = :user_id
    ORDER BY b.CreatedDate DESC
");
$stmt->execute(['user_id' => $userId]);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transaction / Batch Entry</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">

<style>
body { margin: 0; min-height: 100vh; background-color: #f8f9fa; }
.topbar-fixed { position: fixed; top: 0; width: 100%; z-index: 1030; }
.sidebar-fixed { position: fixed; top: 70px; bottom: 0; width: 240px; overflow-y: auto; background-color: #343a40; }
.page-content { margin-left: 240px; padding: 150px 20px 20px 20px; }
.table-responsive { max-width: 1000px; margin: auto; }
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
                    <th>ID</th>
                    <th>Bill No</th>
                    <th>Received</th>
                    <th>Alloted To</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php foreach($rows as $r): ?>
    <tr>
        <td><?= $r['Id'] ?></td>
        <td><?= htmlspecialchars($r['BillNumber']) ?></td>
        <td><?= htmlspecialchars($r['BillReceivedDate']) ?></td>
        <td><?= htmlspecialchars($r['AllotedName']) ?></td>

        <td>
            <?php if($r['Status'] == 'Pending'): ?>
                <span class="badge bg-warning text-dark"><?= $r['Status'] ?></span>
            <?php elseif($r['Status'] == 'Returned'): ?>
                <span class="badge bg-danger text-dark"><?= $r['Status'] ?></span>
            <?php else: ?>
                <span class="badge bg-info text-dark"><?= $r['Status'] ?></span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($r['Remarks']) ?></td>
        <td>
            <button 
                class="btn btn-sm btn-primary process-btn"
                data-id="<?= $r['BillInitialId'] ?>"
                <?= ($r['Status'] == 'Returned') ? 'disabled' : '' ?>
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

<!-- Include JS libraries -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../js/datatables/dataTables.bootstrap5.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/datatables/jquery.dataTables.min.js"></script>
<script src="../js/datatables/dataTables.bootstrap5.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    $('#billsTable').DataTable({
        "lengthMenu": [5, 10, 25, 50],
        "pageLength": 10,
        "ordering": true,
        "columnDefs": [
            { "orderable": false, "targets": 5 } // Disable sorting on Action column
        ]
    });

    // SweetAlert confirmation before processing
    

    $('.process-btn').click(function(){
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
    // Create a temporary form
    var form = $('<form action="process_bill.php" method="POST">' +
                 '<input type="hidden" name="bill_id" value="' + billId + '"></form>');

    $('body').append(form); // Add form to DOM
    form.submit();    
    }
        });      // Submit form
});
});
</script>

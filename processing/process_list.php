<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(2);

// Fetch bills with status Pending or Returned
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT b.*, e.EmployeeName AS AllotedName 
    FROM bill_entry b
    LEFT JOIN employee_master e ON b.AllotedDealingAsst = e.Id
    WHERE b.Status IN ('Pending','Returned') 
      AND b.AllotedDealingAsst = :user_id
    ORDER BY b.CreatedDate DESC
");
$stmt->execute(['user_id' => $userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../header/header_processing.php'; ?>

<div class="container mt-5">
    <h3 class="mb-4 text-center">Bills to Process</h3>

    <div class="table-responsive shadow rounded">
        <table id="billsTable" class="table table-striped table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Bill No</th>
                    <th>Received</th>
                    <th>Alloted To</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php foreach($rows as $r): ?>
    <tr>
        <td><?= $r['Id'] ?></td>
        <td><?= htmlspecialchars($r['BillNo']) ?></td>
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

        <td>
            <button 
                class="btn btn-sm btn-primary process-btn"
                data-id="<?= $r['Id'] ?>"
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

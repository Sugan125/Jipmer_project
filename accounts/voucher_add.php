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



// Get bill_id from POST (hidden input from previous page)
$billId = intval($_POST['bill_id'] ?? 0);
if (!$billId) {
    header('Location: accounts_pending.php');
    exit;
}

// Check if voucher already exists
$exists = $conn->prepare("SELECT 1 FROM final_accounts WHERE BillId = ?");
$exists->execute([$billId]);
if ($exists->fetch()) {
    header('Location: accounts_pending.php');
    exit;
}

?>
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container" style="margin-top:10rem;">
    <div class="card shadow rounded">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-file-invoice-dollar"></i> Voucher Entry for Bill #<?= htmlspecialchars($billId) ?></h4>
        </div>
        <div class="card-body">
            <form id="voucherForm">
                <input type="hidden" name="bill_id" value="<?= $billId ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Resubmitted On</label>
                        <input type="date" name="resubmitted_on" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">PFMS Advice No</label>
                        <input type="text" name="advice" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Voucher No <span class="text-danger">*</span></label>
                        <input type="text" name="voucher" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Voucher Date</label>
                        <input type="date" name="voucher_date" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks <span class="text-danger">*</span></label>
                    <textarea name="remarks" class="form-control" required></textarea>
                </div>

                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Voucher</button>
            </form>
        </div>
    </div>
</div>

<!-- JS/CSS Libraries -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){

    $('#voucherForm').on('submit', function(e){
        e.preventDefault();

        $.ajax({
            url: 'voucher_add_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Voucher Saved!',
                        text: resp.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'accounts_pending.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: resp.message
                    });
                }
            },
            error: function(){
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Something went wrong!'
                });
            }
        });
    });

});
</script>

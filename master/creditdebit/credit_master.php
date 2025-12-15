<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4); // admin only

$credits = $conn->query("SELECT * FROM account_credit_master ORDER BY CreditName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Credit Master</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/all.min.css">
    <link rel="stylesheet" href="../../js/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../css/style.css">

    <style>
        body { background:#f4f6fb; }
        .card-box {
            background:#fff;
            border-radius:12px;
            box-shadow:0 8px 25px rgba(0,0,0,0.06);
            padding:20px;
        }
        .card-header-custom {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:15px;
        }
        .table th {
            background:#eef1f7;
        }
    </style>
</head>
<body>

<?php include '../../header/header_admin.php'; ?>

<div class="container mt-5">
    <div class="card-box">

        <div class="card-header-custom">
            <h5 class="fw-semibold">
                <i class="fa-solid fa-credit-card text-primary me-2"></i>
                Account Credit Master
            </h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fa-solid fa-plus me-1"></i> Add Credit
            </button>
        </div>

        <table id="creditTable" class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Credit Name</th>
                    <th width="120">Status</th>
                    <th width="160">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($credits as $i=>$c): ?>
                <tr id="row_<?= $c['Id'] ?>">
                    <td><?= $i+1 ?></td>
                    <td class="credit_name"><?= htmlspecialchars($c['CreditName']) ?></td>
                    <td class="credit_status">
                        <?= $c['Status'] == 1
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-secondary">Inactive</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning editBtn"
                                data-id="<?= $c['Id'] ?>"
                                data-name="<?= htmlspecialchars($c['CreditName']) ?>"
                                data-status="<?= $c['Status'] ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $c['Id'] ?>">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal">
<div class="modal-dialog">
<form id="addForm" method="post" class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Add Credit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Credit Name</label>
            <input type="text" name="CreditName" class="form-control" required>
        </div>
        <input type="hidden" name="Status" id="add_status_val" value="1">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="add_status" checked>
            <label class="form-check-label" for="add_status">Active</label>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
</div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal">
<div class="modal-dialog">
<form id="editForm" method="post" class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Edit Credit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        <input type="hidden" name="id" id="edit_id">
        <input type="hidden" name="Status" id="edit_status_val" value="1">

        <div class="mb-3">
            <label class="form-label">Credit Name</label>
            <input type="text" name="CreditName" id="edit_name" class="form-control" required>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="edit_status">
            <label class="form-check-label" for="edit_status">Active</label>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
</div>
</div>

<!-- Scripts -->
<script src="../../js/jquery-3.7.1.min.js"></script>
<script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../../js/sweetalert2.all.min.js"></script>
<script src="../../js/datatables/jquery.dataTables.min.js"></script>
<script src="../../js/datatables/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    $('#creditTable').DataTable({ "pageLength": 10 });

    // Toggle hidden input for Add modal
    $('#add_status').change(function(){
        $('#add_status_val').val(this.checked ? 1 : 0);
    });

    // Toggle hidden input for Edit modal
    $('#edit_status').change(function(){
        $('#edit_status_val').val(this.checked ? 1 : 0);
    });

    // EDIT modal
    $('.editBtn').click(function(){
        $('#edit_id').val($(this).data('id'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_status').prop('checked', $(this).data('status') == 1);
        $('#edit_status_val').val($(this).data('status') == 1 ? 1 : 0);
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // ADD form AJAX
    $('#addForm').submit(function(e){
        e.preventDefault();
        $.post('credit_save.php', $(this).serialize(), function(res){
            location.reload(); // reload to see new row
        });
    });

    // EDIT form AJAX
    $('#editForm').submit(function(e){
        e.preventDefault();
        $.post('credit_edit_save.php', $(this).serialize(), function(res){
            try {
                const data = JSON.parse(res);
                if(data.status === 'success'){
                    Swal.fire('Success', data.message, 'success').then(()=>location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err){
                Swal.fire('Error', 'Unexpected response', 'error');
            }
        });
    });

    // DELETE with SweetAlert
    $('.deleteBtn').click(function(){
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This credit will be deleted permanently!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if(result.isConfirmed){
                $.get('credit_delete.php', {id: id}, function(){
                    $('#row_' + id).remove();
                    Swal.fire('Deleted!', 'Credit has been deleted.', 'success');
                });
            }
        });
    });
});
</script>

</body>
</html>

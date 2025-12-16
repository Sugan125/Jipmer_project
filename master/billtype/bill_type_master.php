<?php
session_start();
include __DIR__ . '/../../config/db.php';
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$page = basename($_SERVER['PHP_SELF']);

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ?
      AND m.PageUrl LIKE ?
      AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);

if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}


$types = $conn->query("SELECT * FROM bill_type_master ORDER BY BillType")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bill Type Master</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../js/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../css/all.min.css">
    <link rel="stylesheet" href="../../css/style.css">
  

    <style>
        body {
            background: #f5f6fa;
        }

        .page-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.05);
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .page-header h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .table th {
            background: #f1f3f8;
        }

        .modal-header {
            background: #f1f3f8;
        }
    </style>
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
        <div class="page-header">
            <h5><i class="fa-solid fa-file-invoice me-2"></i>Bill Type Master</h5>

            <button class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#addBillTypeModal">
                <i class="fa-solid fa-plus me-1"></i> Add Bill Type
            </button>
        </div>

        <!-- TABLE -->
        <table id="billTable" class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Bill Type</th>
                    <th style="width:120px">Status</th>
                    <th style="width:220px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($types as $i => $t): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($t['BillType']) ?></td>
                    <td>
                        <?= $t['IsActive']
                            ? '<span class="badge bg-success">Active</span>'
                            : '<span class="badge bg-secondary">Inactive</span>' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning edit-btn"
                                data-id="<?= $t['Id'] ?>"
                                data-name="<?= htmlspecialchars($t['BillType']) ?>">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-outline-info toggle-btn"
                                data-id="<?= $t['Id'] ?>"
                                data-status="<?= $t['IsActive'] ?>">
                            <?= $t['IsActive'] ? 'Deactivate' : 'Activate' ?>
                        </button>

                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                data-id="<?= $t['Id'] ?>">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addBillTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addBillTypeForm" class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Add Bill Type</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Bill Type</label>
                <input type="text" name="billtype" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editForm" class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Edit Bill Type</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <label class="form-label">Bill Type</label>
                <input type="text" name="billtype" id="edit_name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary btn-sm">Update</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
  <!-- JS -->
    <script src="../../js/jquery-3.7.1.min.js"></script>
    <script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../../js/datatables/jquery.dataTables.min.js"></script>
    <script src="../../js/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="../../js/sweetalert2.all.min.js"></script>
<script>
$(function(){

    $('#billTable').DataTable();

    // Edit
    $('.edit-btn').click(function(){
        $('#edit_id').val($(this).data('id'));
        $('#edit_name').val($(this).data('name'));
        new bootstrap.Modal('#editModal').show();
    });

    $('#editForm').submit(function(e){
        e.preventDefault();
        $.post('bill_type_edit_save.php', $(this).serialize(), function(resp){
            resp.status === 'success'
                ? Swal.fire('Updated', resp.message, 'success').then(()=>location.reload())
                : Swal.fire('Error', resp.message, 'error');
        }, 'json');
    });

    // Add
    $('#addBillTypeForm').submit(function(e){
        e.preventDefault();
        $.post('bill_type_add.php', $(this).serialize(), function(resp){
            resp.status === 'success'
                ? Swal.fire('Saved', resp.message, 'success').then(()=>location.reload())
                : Swal.fire('Error', resp.message, 'error');
        }, 'json');
    });

    // Toggle
    $('.toggle-btn').click(function(){
        $.post('bill_type_toggle.php', {id:$(this).data('id')}, function(resp){
            resp.status === 'success' ? location.reload() : alert(resp.message);
        }, 'json');
    });

    // Delete
    $('.delete-btn').click(function(){
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete?',
            text: 'This cannot be undone',
            icon: 'warning',
            showCancelButton: true
        }).then(result=>{
            if(result.isConfirmed){
                $.post('bill_type_delete.php', {id:id}, function(resp){
                    resp.status === 'success'
                        ? location.reload()
                        : Swal.fire('Error', resp.message, 'error');
                }, 'json');
            }
        });
    });

});
</script>

</body>
</html>

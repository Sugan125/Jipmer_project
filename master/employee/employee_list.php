<?php
session_start();
include '../../config/db.php';

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

// Fetch roles for edit dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY RoleName")->fetchAll(PDO::FETCH_ASSOC);

// Fetch employees (you may want to exclude super admin etc.)
$employees = $conn->query("SELECT Id,EmpCode, EmployeeName, Username, RoleId FROM employee_master ORDER BY EmployeeName")->fetchAll(PDO::FETCH_ASSOC);
?>
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

<link rel="stylesheet" href="../../css/bootstrap.min.css">
<link rel="stylesheet" href="../../css/all.min.css">
<link rel="stylesheet" href="../../js/datatables/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../css/style.css">

<div class="container mt-4">
    <div class="d-flex align-items-center mb-3">
        <h3 class="me-auto">Employee List</h3>

        <!-- toggle table style -->
        <div class="btn-group btn-group-sm" role="group" aria-label="table styles">
            <button id="btnStriped" class="btn btn-outline-secondary active">Striped</button>
            <button id="btnClean" class="btn btn-outline-secondary">Clean</button>
        </div>

        <a href="employee_add.php" class="btn btn-primary btn-sm ms-3">➕ Add Employee</a>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table id="employeesTable" class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Emp Code</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($employees as $i => $emp): ?>
                <tr data-id="<?= $emp['Id'] ?>">
                    <td><?= $i+1 ?></td>
                     <td><?= htmlspecialchars($emp['EmpCode']) ?></td>
                    <td><?= htmlspecialchars($emp['EmployeeName']) ?></td>
                    <td><?= htmlspecialchars($emp['Username']) ?></td>
                    <td data-roleid="<?= $emp['RoleId'] ?>">
                        <?php
                        // Lookup role name quickly (you could map server-side instead)
                        $roleName = '';
                        foreach($roles as $r) if($r['RoleId'] == $emp['RoleId']) { $roleName = $r['RoleName']; break; }
                        echo htmlspecialchars($roleName);
                        ?>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary edit-btn" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-btn" title="Delete"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal (reused) -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editEmployeeForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Employee</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="Id" id="Id">

             <div class="mb-3">
                <label class="form-label">Employee Code</label>
               <input type="text" name="EmpCode" id="EmpCode" class="form-control soft-input" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Employee Name</label>
                <input type="text" name="EmployeeName" id="EmployeeName" class="form-control soft-input" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="Username" id="Username" class="form-control soft-input" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" name="Password" id="Password" class="form-control soft-input">
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="RoleId" id="RoleId" class="form-select soft-input" required>
                    <option value="">Select Role</option>
                    <?php foreach($roles as $r): ?>
                        <option value="<?= $r['RoleId'] ?>"><?= htmlspecialchars($r['RoleName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- scripts -->
<script src="../../js/jquery-3.7.1.min.js"></script>
<script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../../js/datatables/jquery.dataTables.min.js"></script>
<script src="../../js/datatables/dataTables.bootstrap5.min.js"></script>
<script src="../../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){

    // Initialize DataTable
    var table = $('#employeesTable').DataTable({
        lengthMenu: [5,10,25,50],
        pageLength: 10,
        columnDefs: [
            { orderable: false, targets: 4 } // action column
        ]
    });

    // Table style toggles
    $('#btnStriped').click(function(){
        $(this).addClass('active'); $('#btnClean').removeClass('active');
        $('#employeesTable').addClass('table-striped').removeClass('table-borderless');
    });
    $('#btnClean').click(function(){
        $(this).addClass('active'); $('#btnStriped').removeClass('active');
        $('#employeesTable').removeClass('table-striped').addClass('table-borderless');
    });

    // Edit button - open modal and fill data via AJAX
    $(document).on('click', '.edit-btn', function(){
        var tr = $(this).closest('tr');
        var id = tr.data('id');

        // fetch employee details
        $.ajax({
            url: 'get_employee_ajax.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    $('#Id').val(resp.data.Id);
                    $('#EmpCode').val(resp.data.EmpCode);
                    $('#EmployeeName').val(resp.data.EmployeeName);
                    $('#Username').val(resp.data.Username);
                    $('#Password').val(''); // blank
                    $('#RoleId').val(resp.data.RoleId);
                    var editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
                    editModal.show();
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            },
            error: function(){ Swal.fire('Error','Could not fetch employee data','error'); }
        });
    });

    // Update via AJAX
    $('#editEmployeeForm').submit(function(e){
        e.preventDefault();
        $.ajax({
            url: 'update_employee_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    Swal.fire({ icon: 'success', title: 'Saved', text: resp.message, timer:1500, showConfirmButton:false });
                    // update row in table without reload
                    var row = $('#employeesTable').find('tr[data-id="'+ resp.data.Id +'"]');

                    row.find('td').eq(1).text(resp.data.EmpCode);
                    row.find('td').eq(2).text(resp.data.EmployeeName);
                    row.find('td').eq(3).text(resp.data.Username);
                    row.find('td').eq(4).text(resp.data.RoleName);
                    $('#editEmployeeModal').modal('hide');
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            },
            error: function(){ Swal.fire('Error','Server error', 'error'); }
        });
    });

    // Delete with confirmation
    $(document).on('click', '.delete-btn', function(){
        var tr = $(this).closest('tr');
        var id = tr.data('id');

        Swal.fire({
            title: 'Delete this employee?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result)=>{
            if(result.isConfirmed){
                $.ajax({
                    url: 'delete_employee_ajax.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(resp){
                        if(resp.status === 'success'){
                            Swal.fire('Deleted', resp.message, 'success');
                            // remove row from table
                            table.row(tr).remove().draw();
                        } else {
                            Swal.fire('Error', resp.message, 'error');
                        }
                    },
                    error: function(){ Swal.fire('Error','Server error','error'); }
                });
            }
        });
    });

});
</script>

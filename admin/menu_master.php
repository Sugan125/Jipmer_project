<?php
session_start();
include '../config/db.php';


// Fetch menus
$menubar = $conn->query("SELECT MenuId, MenuName, PageUrl,Status, IconClass, SortOrder FROM menu_master ORDER BY SortOrder ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Menu Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/sweetalert2.all.min.js"></script>
</head>
<body class="soft-bg">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div style="margin-left:240px; padding:20px;">
    <h3 class="mb-4">Menu Management</h3>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#menuModal">➕ Add Menu</button>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Menu Name</th>
                <th>Page URL</th>
                <th>Icon</th>
                <th>Sort Order</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($menubar as $m): 
          
            ?>
            <tr>
                <td><?= $m['MenuId'] ?? '' ?></td>
                <td><?= htmlspecialchars($m['MenuName'] ?? '') ?></td>
                <td><?= htmlspecialchars($m['PageUrl'] ?? '') ?></td>
                <td><i class="<?= $m['IconClass'] ?? '' ?>"></i></td>
                <td><?= $m['SortOrder'] ?? 1 ?></td>
                <td><?= isset($m['Status']) && $m['Status'] ? 'Active' : 'Inactive' ?></td>
                <td>
                    <button class="btn btn-sm btn-info editMenuBtn" data-id="<?= $m['MenuId'] ?? 0 ?>">Edit</button>
                    <button class="btn btn-sm btn-danger deleteMenuBtn" data-id="<?= $m['MenuId'] ?? 0 ?>">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="menuForm">
        <div class="modal-header">
          <h5 class="modal-title" id="menuModalTitle">Add Menu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="MenuId" id="MenuId">
            <div class="mb-3">
                <label>Menu Name</label>
                <input type="text" name="MenuName" id="MenuName" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Page URL</label>
                <input type="text" name="PageUrl" id="PageUrl" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Icon Class</label>
                <input type="text" name="IconClass" id="IconClass" class="form-control" placeholder="fas fa-home">
            </div>
            <div class="mb-3">
                <label>Sort Order</label>
                <input type="number" name="SortOrder" id="SortOrder" class="form-control" value="1">
            </div>
            <div class="mb-3">
                <label>Status</label>
                <select name="Status" id="Status" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Menu</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){

    // Open modal for edit
    $(".editMenuBtn").click(function(){
        var id = $(this).data('id');
        $.post('menu_action.php', {action:'get', MenuId:id}, function(res){
            if(res.status === 'success'){
                $("#MenuId").val(res.data.MenuId ?? '');
                $("#MenuName").val(res.data.MenuName ?? '');
                $("#PageUrl").val(res.data.PageUrl ?? '');
                $("#IconClass").val(res.data.IconClass ?? '');
                $("#SortOrder").val(res.data.SortOrder ?? 1);
                $("#Status").val(res.data.Status ?? 1);
                $("#menuModalTitle").text("Edit Menu");
                new bootstrap.Modal(document.getElementById('menuModal')).show();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Add/Edit submit
    $("#menuForm").submit(function(e){
        e.preventDefault();
        $.post('menu_action.php', $(this).serialize(), function(res){
            if(res.status==='success'){
                Swal.fire('Success', res.message, 'success').then(()=> location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });

    // Delete
    $(".deleteMenuBtn").click(function(){
        var id = $(this).data('id');
        Swal.fire({
            title:'Are you sure?',
            icon:'warning',
            showCancelButton:true,
            confirmButtonText:'Yes, Delete'
        }).then((result)=>{
            if(result.isConfirmed){
                $.post('menu_action.php', {action:'delete', MenuId:id}, function(res){
                    if(res.status==='success'){
                        Swal.fire('Deleted!', res.message,'success').then(()=> location.reload());
                    } else {
                        Swal.fire('Error', res.message,'error');
                    }
                }, 'json');
            }
        });
    });

});
</script>

</body>
</html>

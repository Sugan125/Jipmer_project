<?php
session_start();
include '../config/db.php';

$roles = $conn->query("
    SELECT RoleId, RoleName 
    FROM roles 
    ORDER BY RoleId
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Role Management</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/sweetalert2.all.min.js"></script>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div style="margin-left:240px;padding:20px;">
    <h3 class="mb-3">Role Management</h3>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#roleModal">
        ➕ Add Role
    </button>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Role Name</th>
                <th width="180">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($roles as $r): ?>
            <tr>
                <td><?= $r['RoleId'] ?></td>
                <td><?= htmlspecialchars($r['RoleName']) ?></td>
                <td>
                    <button class="btn btn-sm btn-info editRoleBtn"
                        data-id="<?= $r['RoleId'] ?>"
                        data-name="<?= htmlspecialchars($r['RoleName']) ?>">
                        Edit
                    </button>

                  
                    <button class="btn btn-sm btn-danger deleteRoleBtn"
                        data-id="<?= $r['RoleId'] ?>">
                        Delete
                    </button>
                  
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="roleModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="roleForm">
        <div class="modal-header">
            <h5 class="modal-title">Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" name="RoleId" id="RoleId">
            <input type="hidden" name="action" value="save">

            <label>Role Name</label>
            <input type="text" name="RoleName" id="RoleName"
                   class="form-control" required>
        </div>

        <div class="modal-footer">
            <button class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){

    $(".editRoleBtn").click(function(){
        $("#RoleId").val($(this).data("id"));
        $("#RoleName").val($(this).data("name"));
        $("#roleModal").modal("show");
    });

    $("#roleForm").submit(function(e){
        e.preventDefault();
        $.post("role_action.php", $(this).serialize(), function(res){
            if(res.status==="success"){
                Swal.fire("Success",res.message,"success")
                    .then(()=>location.reload());
            }else{
                Swal.fire("Error",res.message,"error");
            }
        },'json');
    });

    $(".deleteRoleBtn").click(function(){
        let id = $(this).data("id");
        Swal.fire({
            title:"Delete role?",
            icon:"warning",
            showCancelButton:true
        }).then((r)=>{
            if(r.isConfirmed){
                $.post("role_action.php",{action:"delete",RoleId:id},function(res){
                    if(res.status==="success"){
                        location.reload();
                    }else{
                        Swal.fire("Error",res.message,"error");
                    }
                },'json');
            }
        });
    });

});
</script>
</body>
</html>

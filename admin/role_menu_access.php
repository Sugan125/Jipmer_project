<?php
session_start();
include '../config/db.php';


/* Fetch roles */
$roles = $conn->query("SELECT RoleId, RoleName FROM roles ORDER BY RoleName")
              ->fetchAll(PDO::FETCH_ASSOC);

/* Fetch menus */
$menus = $conn->query("SELECT MenuId, MenuName FROM menu_master WHERE Status = 1 ORDER BY MenuName")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Role Menu Access</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
</head>

<body>
<?php include '../layout/topbar.php'; ?>

<div class="container mt-4">
    <h4 class="mb-3">🔐 Role – Menu Access Control</h4>

    <!-- Role Selection -->
    <div class="mb-3">
        <label class="form-label fw-bold">Select Role</label>
        <select id="roleId" class="form-select">
            <option value="">-- Select Role --</option>
            <?php foreach($roles as $r): ?>
                <option value="<?= $r['RoleId'] ?>"><?= $r['RoleName'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Menu List -->
    <div id="menuArea" style="display:none;">
        <h5 class="mt-4">Menus</h5>

        <form id="menuForm">
            <div class="row">
                <?php foreach($menus as $m): ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input menuCheck"
                                   type="checkbox"
                                   value="<?= $m['MenuId'] ?>"
                                   id="menu<?= $m['MenuId'] ?>">
                            <label class="form-check-label">
                                <?= $m['MenuName'] ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary mt-3">
                <i class="fas fa-save"></i> Save Access
            </button>
        </form>
    </div>
</div>

<script>
$("#roleId").change(function(){
    let roleId = $(this).val();
    if(!roleId) return;

    $("#menuArea").show();
    $(".menuCheck").prop('checked', false);

    $.get("role_menu_fetch.php", {roleId}, function(res){
        res.forEach(id => $("#menu"+id).prop('checked', true));
    }, "json");
});

$("#menuForm").submit(function(e){
    e.preventDefault();

    let roleId = $("#roleId").val();
    let menus = $(".menuCheck:checked").map(function(){
        return $(this).val();
    }).get();

    $.post("role_menu_save.php", {roleId, menus}, function(res){
        Swal.fire("Saved", res.message, "success");
    }, "json");
});
</script>
</body>
</html>

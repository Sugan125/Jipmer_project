<?php
session_start();
include __DIR__ . '/../../config/db.php'; // db + BASE_URL
$page = basename($_SERVER['PHP_SELF']);

// Permission check
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) die("Unauthorized Access");

// Fetch roles
$roles = $conn->query("SELECT * FROM roles ORDER BY RoleId")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <script src="<?= BASE_URL ?>/js/jquery-3.7.1.min.js"></script>
    <script src="<?= BASE_URL ?>/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/js/sweetalert2.all.min.js"></script>
</head>
<body class="soft-bg">
<?php include __DIR__ . '/../../layout/topbar.php'; ?>
<?php include __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card employee-form-card shadow-sm">
                <div class="card-header soft-header">
                    <h4 class="mb-0"><i class="fa-solid fa-user-plus"></i> Add New Employee</h4>
                </div>

                <div class="card-body">
                    <form id="employeeForm">

                        <div class="mb-3">
                            <label class="form-label">Employee Code</label>
                            <input type="text" name="EmployeeCode" class="form-control soft-input" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" name="EmployeeName" class="form-control soft-input" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="Username" class="form-control soft-input" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="Password" class="form-control soft-input" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="RoleId" class="form-select soft-input" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['RoleId'] ?>"><?= htmlspecialchars($r['RoleName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                     <div class="d-flex justify-content-between mt-3">
   

                        <a href="employee_list.php" class="btn btn-outline-secondary btn-sm soft-btn">
                            <i class="fa-solid fa-eye me-1"></i> View Employees
                        </a>
                        <button type="submit" class="btn soft-btn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Employee
                        </button>
                    </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $("#employeeForm").on("submit", function(e){
        e.preventDefault();

        $.ajax({
            url: "save_employee_ajax.php",
            type: "POST",
            data: $("#employeeForm").serialize(),
            dataType: "json",

            success: function(response){
                if(response.status === "success"){
                    Swal.fire({
                        icon: "success",
                        title: "Employee Added",
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $("#employeeForm")[0].reset();
                }
                else {
                    Swal.fire({ icon:"error", title:"Error", text:response.message });
                }
            },

            error: function(){
                Swal.fire({
                    icon: "error",
                    title: "Server Error",
                    text: "Something went wrong. Try again."
                });
            }

        });
    });
});
</script>

</body>
</html>

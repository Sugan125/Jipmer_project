<?php
session_start();
include '../../config/db.php';

// Role 4 only
if (!isset($_SESSION['role']) || $_SESSION['role'] != 4) {
    header("Location: ../../auth/login.php");
    exit;
}

// Fetch roles
$roles = $conn->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>

    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/all.min.css">

    <!-- Custom soft-theme styles -->
    <link rel="stylesheet" href="../../css/style.css">

    <script src="../../js/jquery-3.7.1.min.js"></script>
    <script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../../js/sweetalert2.all.min.js"></script>
</head>

<body class="soft-bg">
<?php include '../../header/header_admin.php'; ?>

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

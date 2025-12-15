<?php
include '../config/db.php';
include '../includes/auth.php';

// Authorization check
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) die("Unauthorized Access");

// Fetch dropdown data
$emps      = $conn->query("SELECT Id, EmployeeName FROM employee_master WHERE Status=1 and RoleId = 2 ORDER BY EmployeeName")->fetchAll(PDO::FETCH_ASSOC);
$bill_type = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1 and IsActive =1 ORDER BY BillType")->fetchAll(PDO::FETCH_ASSOC);
$credit    = $conn->query("SELECT Id, CreditName FROM account_credit_master WHERE Status=1")->fetchAll();
$debit     = $conn->query("SELECT Id, DebitName FROM account_debit_master WHERE Status=1")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Bill Entry</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

<style>
body {
    min-height: 100vh;
    margin: 0;
}

.topbar-fixed {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1030;
}

.sidebar-fixed {
    position: fixed;
    top: 70px; /* height of topbar */
    bottom: 0;
    width: 240px;
    overflow-y: auto;
    background-color: #343a40;
}

.page-content {
    margin-left: 240px; /* sidebar width */
    padding: 50px 20px 20px 20px; /* topbar + spacing */
    display: flex;
    justify-content: center;
}

.form-card {
    width: 100%;
    max-width: 700px;
}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <div class="card p-4 form-card shadow">
        <h4 class="form-title mb-4">
            <i class="fa fa-receipt me-2"></i> Bill Entry
        </h4>

        <form id="billForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bill No</label>
                    <input type="text" name="billno" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bill Received Date</label>
                    <input type="date" name="billdate" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Received From Section</label>
                    <input type="text" name="fromsection" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Section DA Name</label>
                    <input type="text" name="sdaname" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Token No</label>
                    <input type="text" name="tokno" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bill Type</label>
                    <select name="BillTypeId" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach($bill_type as $b): ?>
                            <option value="<?= $b['Id'] ?>"><?= $b['BillType'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Alloted Dealing Assistant</label>
                    <select name="alloted" class="form-select">
                        <option value="">Select</option>
                        <?php foreach($emps as $e): ?>
                            <option value="<?= $e['Id'] ?>"><?= $e['EmployeeName'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Allot Date</label>
                    <input type="date" name="allotdate" class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Account Credit To</label>
                    <select name="CreditToId" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach($credit as $c): ?>
                            <option value="<?= $c['Id'] ?>"><?= $c['CreditName'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Account Debit From</label>
                    <select name="DebitFromId" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach($debit as $d): ?>
                            <option value="<?= $d['Id'] ?>"><?= $d['DebitName'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="bill_entry_list.php" class="btn btn-primary">
                    <i class="fa-solid fa-eye me-1"></i> View Bills
                </a>
                <button class="btn btn-primary save-btn">
                    <i class="fa fa-save me-2"></i> Save Bill
                </button>
            </div>
        </form>
    </div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$("#billForm").on("submit", function(e){
    e.preventDefault();
    $.ajax({
        url: "bill_entry_submit.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(r){
            if(r.status === "success"){
                Swal.fire({
                    icon: "success",
                    title: "Bill Saved",
                    text: "Your bill has been successfully recorded.",
                    timer: 1500,
                    showConfirmButton: false
                });
                $("#billForm")[0].reset();
            } else {
                Swal.fire({ icon:"error", title:"Error", text:r.message });
            }
        },
        error: function(){
            Swal.fire({ icon:"error", title:"Server Error", text:"Unable to save right now." });
        }
    });
});
</script>

</body>
</html>

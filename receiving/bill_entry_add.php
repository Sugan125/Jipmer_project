<?php
// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


// Get bill id from GET
$initial_id = (int)($_GET['id'] ?? 0);
$init = null;
$invoices = [];

if($initial_id){
    // Fetch selected bill
    $stmt = $conn->prepare("
        SELECT b.*, btm.BillType 
        FROM bill_initial_entry b
        LEFT JOIN bill_type_master btm ON btm.Id = b.BillTypeId
        WHERE b.Id=?
    ");
    $stmt->execute([$initial_id]);
    $init = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch invoices attached to this bill
    try {
        $stmt = $conn->prepare("
            SELECT im.*
            FROM invoice_master im
            JOIN bill_invoice_map bim ON bim.InvoiceId = im.Id
            WHERE bim.BillInitialId=?
        ");
        $stmt->execute([$initial_id]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e){
        $invoices = [];
        error_log("Invoice fetch error: ".$e->getMessage());
    }
}

// Fetch dropdowns
$bill_type = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1 and IsActive =1 ORDER BY BillType")->fetchAll(PDO::FETCH_ASSOC);
$emps      = $conn->query("SELECT Id, EmployeeName FROM employee_master WHERE Status=1 AND RoleId=2 ORDER BY EmployeeName")->fetchAll(PDO::FETCH_ASSOC);
$credit    = $conn->query("SELECT Id, CreditName FROM account_credit_master WHERE Status=1")->fetchAll();
$debit     = $conn->query("SELECT Id, DebitName FROM account_debit_master WHERE Status=1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Entry</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
body { min-height: 100vh; margin: 0; }
.topbar-fixed { position: fixed; top: 0; width: 100%; z-index: 1030; }
.sidebar-fixed { position: fixed; top: 70px; bottom: 0; width: 240px; overflow-y: auto; background-color: #343a40; }
.page-content { margin-left: 240px; padding: 50px 20px 20px 20px; display: flex; flex-direction: column; gap: 20px; }
.form-card { width: 100%; max-width: 800px; }
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<?php if($init): ?>

    <!-- Bill Details -->
    <div class="card p-3 shadow">
        <h4 class="text-primary mb-3">📄 Bill Details - <?= htmlspecialchars($init['BillNumber']) ?></h4>
        <div class="row">
            <div class="col-md-4"><strong>Bill Number:</strong> <?= htmlspecialchars($init['BillNumber']) ?></div>
            <div class="col-md-4"><strong>Bill Type:</strong> <?= htmlspecialchars($init['BillType']) ?></div>
            <div class="col-md-4"><strong>Received From:</strong> <?= htmlspecialchars($init['ReceivedFromSection']) ?></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-4"><strong>Received Date:</strong> <?= $init['BillReceivedDate'] ?></div>
            <div class="col-md-4"><strong>Status:</strong> <?= htmlspecialchars($init['Status']) ?></div>
        </div>
    </div>

    <!-- Attached Invoices -->
    <div class="card p-3 shadow">
        <h5 class="text-secondary mb-3">📄 Attached Invoices</h5>
       <?php if($invoices): ?>
<div class="row g-3">
    <?php foreach($invoices as $inv): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border">
            <div class="card-body">
                <h6 class="card-title mb-2">Invoice No: <?= htmlspecialchars($inv['InvoiceNo']) ?></h6>
                <p class="mb-1"><strong>Invoice Date:</strong> <?= $inv['InvoiceDate'] ?></p>
                <p class="mb-1"><strong>Sanction Order:</strong> <?= htmlspecialchars($inv['SanctionOrderNo']) ?></p>
                <p class="mb-1"><strong>Sanction Date:</strong> <?= $inv['SanctionDate'] ?></p>
                <p class="mb-1"><strong>Vendor Name:</strong> <?= htmlspecialchars($inv['VendorName']) ?></p>
                <p class="mb-0"><strong>Account Details:</strong> <?= htmlspecialchars($inv['AccountDetails']) ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<p class="text-muted">No invoices attached.</p>
<?php endif; ?>
    </div>

    <!-- Bill Entry Form -->
    <div class="card p-4 form-card shadow">
        <h4 class="mb-4 text-primary">✏️ Bill Entry for #<?= htmlspecialchars($init['BillNumber']) ?></h4>
        <form id="billForm">
            <input type="hidden" name="initial_id" value="<?= $init['Id'] ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Token No</label>
                    <input type="text" name="tokno" class="form-control">
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
                    <select name="CreditToId" class="form-select">
                        <option value="">Select</option>
                        <?php foreach($credit as $c): ?>
                        <option value="<?= $c['Id'] ?>"><?= htmlspecialchars($c['CreditName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Account Debit From</label>
                    <select name="DebitFromId" class="form-select">
                        <option value="">Select</option>
                        <?php foreach($debit as $d): ?>
                        <option value="<?= $d['Id'] ?>"><?= htmlspecialchars($d['DebitName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <a href="bill_entry_list.php" class="btn btn-secondary">Back</a>
                <button class="btn btn-primary save-btn">💾 Save Bill</button>
            </div>
        </form>
    </div>

<?php else: ?>
    <p class="text-muted">No bill selected.</p>
<?php endif; ?>

</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$("#billForm").on("submit", function(e){
    e.preventDefault();
    $.ajax({
        url: "bill_entry_submit.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (r) {
        if (r.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Saved",
                    text: "Bill successfully recorded.",
                    timer: 1500,
                    showConfirmButton: false,
                    willClose: () => {
                        window.location.href = "bill_entry_list.php";
                    }
                });

                $("#billForm")[0].reset();
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: r.message
                });
            }
        },
            error: function(){
            Swal.fire({icon:"error",title:"Server Error",text:"Unable to save right now."});
        }
    });
});
</script>
</body>
</html>

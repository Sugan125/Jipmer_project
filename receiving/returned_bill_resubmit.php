<?php
include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);


if (!isset($_GET['id'])) {
    die("Invalid Bill ID");
}

$billId = intval($_GET['id']);

// Fetch bill details
$stmt = $conn->prepare("SELECT be.*, bi.BillNumber, bi.BillReceivedDate, bi.ReceivedFromSection, bi.SectionDAName, bi.BillTypeId FROM bill_entry be left join bill_initial_entry bi on bi.Id = be.BillInitialId WHERE be.Id = ?");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill || $bill['Status'] != 'Returned') {
    die("Bill not found or not returned");
}

// Fetch employees for allotment
$emps = $conn->query("SELECT Id, EmployeeName FROM employee_master WHERE Status=1 and RoleId = 2 ORDER BY EmployeeName")
             ->fetchAll(PDO::FETCH_ASSOC);

$bill_type = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1 and IsActive = 1 ORDER BY BillType")
->fetchAll(PDO::FETCH_ASSOC);

    $credit = $conn->query("SELECT Id, CreditName FROM account_credit_master WHERE Status=1")->fetchAll();
$debit = $conn->query("SELECT Id, DebitName FROM account_debit_master WHERE Status=1")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resubmit Returned Bill</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/sweetalert2.all.min.js"></script>
</head>
<body class="bg-light">
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm p-4">
        <h4 class="text-primary fw-bold mb-4">🔄 Resubmit Returned Bill</h4>

        <form id="resubmitForm">
            <input type="hidden" name="id" value="<?= $bill['Id'] ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bill Number</label>
                    <input type="text" name="billno" class="form-control readonly-input" value="<?= htmlspecialchars($bill['BillNumber']) ?>" readonly>
                 
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bill Received Date</label>
                    <input type="date" name="billdate" class="form-control readonly-input" value="<?= $bill['BillReceivedDate'] ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Received From Section</label>
                    <input type="text" name="fromsection" class="form-control readonly-input" value="<?= htmlspecialchars($bill['ReceivedFromSection']) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Section DA Name</label>
                    <input type="text" name="sdaname" class="form-control readonly-input" value="<?= htmlspecialchars($bill['SectionDAName']) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Token No</label>
                    <input type="text" name="tokno" class="form-control" value="<?= $bill['TokenNo'] ?>">
                </div>
                 <div class="col-md-6">
    <label class="form-label">Bill Type</label>
    <select name="BillTypeId" class="form-select readonly-input" disabled>
        <option value="">Select</option>
        <?php foreach ($bill_type as $b): ?>
            <option value="<?= $b['Id'] ?>"
                <?= ($b['Id'] == $bill['BillTypeId']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['BillType']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                <div class="col-md-6">
                    <label class="form-label">Alloted Dealing Assistant</label>
                    <select name="alloted" class="form-select">
                        <option value="">Select</option>
                        <?php foreach($emps as $e): ?>
                            <option value="<?= $e['Id'] ?>" <?= ($bill['AllotedDealingAsst'] == $e['Id']) ? 'selected' : '' ?>>
                                <?= $e['EmployeeName'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Allot Date</label>
                    <input type="date" name="allotdate" class="form-control" value="<?= $bill['AllotedDate'] ?>">
                </div>

                 <div class="col-md-6">
    <label>Account Credit To</label>
    <select name="CreditToId" class="form-select" required>
        <option value="">Select</option>
        <?php foreach ($credit as $c): ?>
            <option value="<?= $c['Id'] ?>"
                <?= ($c['Id'] == $bill['CreditToId']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['CreditName']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>



            <div class="col-md-6">
    <label>Account Debit From</label>
    <select name="DebitFromId" class="form-select" required>
        <option value="">Select</option>
        <?php foreach ($debit as $d): ?>
            <option value="<?= $d['Id'] ?>"
                <?= ($d['Id'] == $bill['DebitFromId']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['DebitName']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($bill['Remarks']) ?></textarea>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i> Resubmit
                </button>
                <a href="returned_bills.php" class="btn btn-secondary ms-2">Back to Returned Bills</a>
            </div>
        </form>

    </div>
</div>

<script>
$("#resubmitForm").on("submit", function(e){
    e.preventDefault();

    $.ajax({
        url: "returned_bill_resubmit_submit.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(res){
            if(res.status === 'success'){
                Swal.fire({
                    icon: 'success',
                    title: 'Bill Resubmitted',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'returned_bills.php';
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function(){
            Swal.fire('Error', 'Server error occurred', 'error');
        }
    });
});
</script>

</body>
</html>

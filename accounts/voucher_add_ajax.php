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



header('Content-Type: application/json');

$billId = intval($_POST['bill_id'] ?? 0);
$resub = $_POST['resubmitted_on'] ?? null;
$advice = trim($_POST['advice'] ?? '');
$voucher = trim($_POST['voucher'] ?? '');
$vdate = $_POST['voucher_date'] ?? null;
$remarks = trim($_POST['remarks'] ?? '');

if (!$billId || $voucher === '' || $remarks === '') {
    echo json_encode(['status' => 'error', 'message' => 'Voucher No and Remarks are required.']);
    exit;
}

// Check for duplicate voucher number
$chk = $conn->prepare("SELECT COUNT(*) FROM final_accounts WHERE VoucherNo = ?");
$chk->execute([$voucher]);
if ($chk->fetchColumn() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Voucher number already exists.']);
    exit;
}

// Insert voucher
$stmt = $conn->prepare("INSERT INTO final_accounts 
    (BillId, ResubmittedOn, PFMSAdviceNo, VoucherNo, VoucherDate, Remarks, CreatedBy, CreatedIP)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $billId,
    $resub,
    $advice,
    $voucher,
    $vdate,
    $remarks,
    $_SESSION['user_id'],
    $_SERVER['REMOTE_ADDR']
]);

$update = $conn->prepare("UPDATE bill_entry SET Status='Voucher Done' WHERE Id=?");
$update->execute([$billId]);

echo json_encode(['status' => 'success', 'message' => 'Voucher has been saved successfully.']);
exit;

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

$data = $_POST;
$billInitialId = (int)($_POST['initial_id'] ?? 0);

if($billInitialId <= 0){
    echo json_encode(['status'=>'error','message'=>'Invalid Bill Initial ID']);
    exit;
}
$credit  = (int)($_POST['CreditToId'] ?? 0);
$debit   = (int)($_POST['DebitFromId'] ?? 0);
$tokno = $data['tokno'] ?? '';
$alloted = intval($data['alloted'] ?? 0);
$allotdate = $data['allotdate'] ?? null;
$remarks = $data['remarks'] ?? '';


if ($tokno === '' || $alloted === 0 || trim($remarks) === '') {
    echo json_encode(['status'=>'error','message'=>'Tokenno, Alloted and Remarks are required']); exit;
}

try {
 $stmt = $conn->prepare("
INSERT INTO bill_entry
(BillInitialId, CreditToId, DebitFromId, TokenNo, AllotedDealingAsst, AllotedDate, Remarks, CreatedBy, CreatedIP, Status)
VALUES (?,?,?,?,?,?,?,?,?,'Pending')
");

$stmt->execute([
    $billInitialId,
    $credit,
    $debit,
    $tokno,
    $alloted,
    $allotdate,
    $remarks,
    $_SESSION['user_id'],
    $_SERVER['REMOTE_ADDR']
]);
    echo json_encode(['status'=>'success']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
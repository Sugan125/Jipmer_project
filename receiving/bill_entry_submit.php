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
$billno = trim($data['billno'] ?? '');
$credit  = (int)($_POST['CreditToId'] ?? 0);
$debit   = (int)($_POST['DebitFromId'] ?? 0);
$billtype = (int)($_POST['BillTypeId'] ?? 0);
$billdate = $data['billdate'] ?? null;
$fromsection = $data['fromsection'] ?? '';
$sdaname = $data['sdaname'] ?? '';
$tokno = $data['tokno'] ?? '';
$alloted = intval($data['alloted'] ?? 0);
$allotdate = $data['allotdate'] ?? null;
$remarks = $data['remarks'] ?? '';
if ($billtype <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Bill Type selected'
    ]);
    exit;
}

$chk = $conn->prepare("SELECT COUNT(*) FROM bill_type_master WHERE Id = ?");
$chk->execute([$billtype]);

if ($chk->fetchColumn() == 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Selected Bill Type does not exist'
    ]);
    exit;
}
if ($billno === '' || $alloted === 0 || trim($remarks) === '') {
    echo json_encode(['status'=>'error','message'=>'BillNo, Alloted and Remarks are required']); exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO bill_entry (BillNo,BillTypeId,CreditToId,DebitFromId, BillReceivedDate, ReceivedFromSection, SectionDAName, TokenNo, AllotedDealingAsst, AllotedDate, Remarks, CreatedBy, CreatedIP)
                            VALUES (?, ?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$billno,$billtype,$credit,$debit, $billdate, $fromsection, $sdaname, $tokno, $alloted, $allotdate, $remarks, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    echo json_encode(['status'=>'success']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
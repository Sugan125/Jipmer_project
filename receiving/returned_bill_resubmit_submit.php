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
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$billno = trim($_POST['billno'] ?? '');
$billtype = intval($_POST['BillTypeId'] ?? 0);
$CreditToId = intval($_POST['CreditToId'] ?? 0);
$DebitFromId = intval($_POST['DebitFromId'] ?? 0);
$billdate = $_POST['billdate'] ?? null;
$fromsection = $_POST['fromsection'] ?? '';
$sdaname = $_POST['sdaname'] ?? '';
$tokno = $_POST['tokno'] ?? '';
$alloted = intval($_POST['alloted'] ?? 0);
$allotdate = $_POST['allotdate'] ?? null;
$remarks = $_POST['remarks'] ?? '';
$updatedby = $_SESSION['user_id'];

if ($id === 0 || $billno === '' || $billtype === 0 || $alloted === 0 || trim($remarks) === '' || $CreditToId === 0 || $DebitFromId === 0) {
    echo json_encode(['status'=>'error','message'=>'Required fields missing']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE bill_entry SET 
                            BillNo=?, BillTypeId=?,CreditToId=?, DebitFromId=?, BillReceivedDate=?, ReceivedFromSection=?, SectionDAName=?, TokenNo=?, 
                            AllotedDealingAsst=?, AllotedDate=?, Remarks=?, Status='Pending', UpdatedDate=GETDATE(), UpdatedBy = ?
                            WHERE Id=? AND Status='Returned'");
    $stmt->execute([$billno,$billtype,$CreditToId,$DebitFromId, $billdate, $fromsection, $sdaname, $tokno, $alloted, $allotdate, $remarks,$updatedby, $id]);

    echo json_encode(['status'=>'success','message'=>'Bill resubmitted successfully']);
} catch(Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

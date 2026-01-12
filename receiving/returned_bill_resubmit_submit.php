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

$id = intval($_POST['id'] ?? 0);
$CreditToId = intval($_POST['CreditToId'] ?? 0);
$DebitFromId = intval($_POST['DebitFromId'] ?? 0);
$tokno = $_POST['tokno'] ?? '';
$alloted = intval($_POST['alloted'] ?? 0);
$allotdate = $_POST['allotdate'] ?? null;
$remarks = $_POST['remarks'] ?? '';
$updatedby = $_SESSION['user_id'];

if ($id === 0 || trim($remarks) === '') {
    echo json_encode(['status'=>'error','message'=>'Required fields missing']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE bill_entry SET  TokenNo=?, 
                            AllotedDealingAsst=?, AllotedDate=?, Remarks=?, Status='Pending', UpdatedDate=GETDATE(), UpdatedBy = ?
                            WHERE Id=? AND Status='Return'");
    $stmt->execute([$tokno, $alloted, $allotdate, $remarks,$updatedby, $id, $id]);

    echo json_encode(['status'=>'success','message'=>'Bill resubmitted successfully']);
} catch(Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

<?php
include '../config/db.php';
include '../includes/auth.php';
header('Content-Type: application/json');

$billId   = intval($_POST['id'] ?? 0);        // matches form name="id"
$token    = trim($_POST['tokno'] ?? '');      // matches form name="tokno"
$allotDate= trim($_POST['allotdate'] ?? '');  // matches form name="allotdate"
$remarks  = trim($_POST['remarks'] ?? '');    // matches form name="remarks"

// Validate required fields
if ($billId === 0 || $token === '' || $allotDate === '' || $remarks === '') {
    echo json_encode(['status'=>'error','message'=>'All fields are required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE bill_entry
        SET TokenNo = ?, AllotedDate = ?, Remarks = ?, Status = 'Pending', concerned_reply = 'N'
        WHERE Id = ?
    ");
    $stmt->execute([$token, $allotDate, $remarks, $billId]);

    echo json_encode(['status'=>'success', 'message'=>'Bill resubmitted successfully']);
} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

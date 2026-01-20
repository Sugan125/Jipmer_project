<?php
include '../config/db.php';
include '../includes/auth.php';
header('Content-Type: application/json');

$billId = intval($_POST['bill_id'] ?? 0);
$reason  = trim($_POST['reason'] ?? '');
$reply   = trim($_POST['reply'] ?? '');

if(!$billId || !$reason || !$reply){
    echo json_encode(['status'=>'error','message'=>'All fields are required']);
    exit;
}

try {
    $conn->beginTransaction();

    // Update bill_entry and mark as reviewed
    $stmt = $conn->prepare("
        UPDATE bill_entry
        SET reviewed = 'Y', Remarks = ?, concerned_reply = 'Y'
        WHERE Id = ?
    ");
    $stmt->execute([$reason, $billId]);

    // Insert or update concerned_section_reply
    $stmt = $conn->prepare("
        MERGE concerned_section_reply AS target
        USING (SELECT ? AS BillId) AS source
        ON target.BillId = source.BillId
        WHEN MATCHED THEN 
            UPDATE SET ReplyText = ?, RepliedBy = ?
        WHEN NOT MATCHED THEN
            INSERT (BillId, ReplyText, RepliedBy) VALUES (?, ?, ?);
    ");
    $stmt->execute([$billId, $reply, $_SESSION['user_id'], $billId, $reply, $_SESSION['user_id']]);

    $conn->commit();
    echo json_encode(['status'=>'success']);
} catch(Exception $e){
    $conn->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

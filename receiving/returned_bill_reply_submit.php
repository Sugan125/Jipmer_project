<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$billId = $_POST['bill_id'] ?? 0;
$reply  = trim($_POST['reply'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;

if (!$billId || !$reply) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid submission']);
    exit;
}

try {
    $conn->beginTransaction();

    // Insert the reply into the concerned_section_reply table
    $stmt = $conn->prepare("
        INSERT INTO concerned_section_reply (BillId, ReplyText, RepliedBy)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$billId, $reply, $userId]);

    // Update the bill_entry table to set concerned_reply to 'Y'
    $stmt = $conn->prepare("
        UPDATE bill_entry
        SET concerned_reply = 'Y'
        WHERE Id = ?
    ");
    $stmt->execute([$billId]);

    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Reply submitted successfully']);
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

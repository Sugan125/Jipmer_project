<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../includes/auth.php';

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $billId = intval($_POST['bill_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // Validate required inputs
    if (!$billId) {
        $response['message'] = 'Invalid Bill ID';
    } elseif (!in_array($status, ['Pass', 'Return'])) {
        $response['message'] = 'Invalid status selected';
    } elseif ($status === 'Return' && $reason === '') {
        $response['message'] = 'Reason is required when status is Return';
    } elseif ($remarks === '') {
        $response['message'] = 'Remarks are required';
    } else {
        try {
            // Insert into bill_process
            $stmt = $conn->prepare("
                INSERT INTO bill_process
                (BillId, Status, ReasonForReturn,ReturnedDate ,Remarks, ProcessedBy, ProcessedIP, ProcessedDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $billId,
                $status,
                $status === 'Return' ? $reason : null,
                date('Y-m-d'),
                $remarks,
                $_SESSION['user_id'],
                $_SERVER['REMOTE_ADDR'],
                date('Y-m-d H:i:s')
            ]);

            // Update bill_entry status
            $u = $conn->prepare("UPDATE bill_entry SET Status = ? WHERE BillInitialId = ?");
            $u->execute([$status, $billId]);

            $response['status'] = 'success';
            $response['message'] = 'Bill processed successfully';
        } catch (Exception $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);

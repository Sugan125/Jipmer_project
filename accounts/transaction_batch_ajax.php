<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$bills   = $_POST['bills'] ?? [];
$batch   = trim($_POST['batch_no'] ?? '');
$voucher = trim($_POST['voucher_no'] ?? ''); // ✅ NEW

if (empty($bills) || !$batch || !$voucher) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$conn->beginTransaction();

try {

    // Prevent duplicate transactions
    $chk = $conn->prepare("
        SELECT 1 FROM bill_transactions
        WHERE BillId IN (" . implode(',', array_fill(0, count($bills), '?')) . ")
    ");
    $chk->execute($bills);

    if ($chk->fetch()) {
        throw new Exception('One or more bills already transacted');
    }

    // ✅ INSERT WITH VOUCHER NO
    $ins = $conn->prepare("
        INSERT INTO bill_transactions
        (BillId, BatchNo, VoucherNo, CreatedBy, CreatedIP)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($bills as $billId) {
        $ins->execute([
            $billId,
            $batch,
            $voucher,              // ✅ NEW
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    // Update bill status
    $upd = $conn->prepare("
        UPDATE bill_entry
        SET Status = 'Transaction Done'
        WHERE Id IN (" . implode(',', array_fill(0, count($bills), '?')) . ")
    ");
    $upd->execute($bills);

    $conn->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Batch & Voucher created for ' . count($bills) . ' bills'
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

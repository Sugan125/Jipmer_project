<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try {

    /* ================= BASIC DATA ================= */
    $amount = (float)$_POST['Amount'];
    $tdsGPercent = (float)$_POST['TDSGSTPercent'];
    $tdsIPercent = (float)$_POST['TDSITPercent'];

    $tdsG = $amount * $tdsGPercent / 100;
    $tdsI = $amount * $tdsIPercent / 100;

    $totalAmount = $amount;
    $netPayable  = $totalAmount - ($tdsG + $tdsI);

    /* ================= SANCTION IDS ================= */
    $sanctionIds = $_POST['SanctionId'] ?? [];

    if (empty($sanctionIds)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No sanction selected'
        ]);
        exit;
    }

    /* ================= VALIDATE SANCTION BALANCE ================= */
    $placeholders = implode(',', array_fill(0, count($sanctionIds), '?'));

    $stmt = $conn->prepare("
        SELECT SUM(SanctionNetAmount)
        FROM sanction_order_master
        WHERE Id IN ($placeholders)
    ");
    $stmt->execute($sanctionIds);

    $totalSanctionBalance = (float)$stmt->fetchColumn();

    if ($amount > $totalSanctionBalance) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invoice amount exceeds combined sanction balance'
        ]);
        exit;
    }

    /* ================= INSERT INVOICE ================= */
    $stmt = $conn->prepare("
        INSERT INTO invoice_master
        (
            FinancialYearId, HOAId, DeptId, BillTypeId,
            CreditToId, DebitFromId,

            InvoiceNo, InvoiceDate, VendorName,
            POId,

            Amount, TotalAmount,

            TDSGSTPercent, TDSGSTAmount,
            TDSITPercent, TDSITAmount,

            NetPayable,

            BankName, IFSC, AccountNumber, PanNumber,
            ReceivedFromSection, SectionDAName,

            CreatedBy
        )
        VALUES (
            ?,?,?,?,?,?,
            ?,?,?,?,
            ?,?,
            ?,?,
            ?,?,
            ?,
            ?,?,?,?,
            ?,?,
            ?
        )
    ");

    $stmt->execute([
        $_POST['FinancialYearId'],
        $_POST['HOAId'],
        $_POST['DeptId'],
        $_POST['BillTypeId'],

        $_POST['CreditToId'],
        $_POST['DebitFromId'],

        $_POST['InvoiceNo'],
        $_POST['InvoiceDate'],
        $_POST['VendorName'],

        $_POST['POId'],

        $amount,
        $totalAmount,

        $tdsGPercent,
        $tdsG,

        $tdsIPercent,
        $tdsI,

        $netPayable,

        $_POST['BankName'],
        $_POST['IFSC'],
        $_POST['AccountNumber'],
        $_POST['PanNumber'],

        $_POST['ReceivedFromSection'],
        $_POST['SectionDAName'],

        $_SESSION['user_id']
    ]);

    $invoiceId = $conn->lastInsertId();

    /* ================= MAP SANCTIONS ================= */
    $mapStmt = $conn->prepare("
        INSERT INTO invoice_sanction_map
        (InvoiceId, SanctionId, AmountUsed)
        VALUES (?,?,?)
    ");

    $remaining = $amount;

    foreach ($sanctionIds as $sid) {

        if ($remaining <= 0) break;

        $balStmt = $conn->prepare("
            SELECT SanctionNetAmount
            FROM sanction_order_master
            WHERE Id = ?
        ");
        $balStmt->execute([$sid]);

        $bal = (float)$balStmt->fetchColumn();

        $use = min($bal, $remaining);
        $remaining -= $use;

        $mapStmt->execute([$invoiceId, $sid, $use]);
    }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

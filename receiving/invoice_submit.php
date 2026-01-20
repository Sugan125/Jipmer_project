<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try {

    /* ================= START TRANSACTION ================= */
    $conn->beginTransaction();


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
        SELECT SUM(
            s.SanctionAmount - ISNULL(used.used_amount, 0)
        )
        FROM sanction_order_master s
        LEFT JOIN (
            SELECT SanctionId, SUM(SanctionBaseAmount) AS used_amount
            FROM invoice_sanction_map
            GROUP BY SanctionId
        ) used ON used.SanctionId = s.Id
        WHERE s.Id IN ($placeholders)
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
            FinancialYearId, HOAId, EcrPageNo, DeptId, BillTypeId,
            CreditToId, DebitFromId,

            InvoiceNo, InvoiceDate, VendorName,
            POId,

            Amount, TotalAmount,

            TDSGSTPercent, TDSGSTAmount,
            TDSITPercent, TDSITAmount,

            NetPayable,

            ReceivedFromSection, SectionDAName,

            CreatedBy
        )
        VALUES (
            ?,?,?,?,?,?,?,
            ?,?,?,?,
            ?,?,
            ?,?,
            ?,?,
            ?,
            ?,?,
            ?
        )
    ");

    $stmt->execute([
        $_POST['FinancialYearId'],
        $_POST['HOAId'],
        $_POST['Ecrpageno'],
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



        $_POST['ReceivedFromSection'],
        $_POST['SectionDAName'],

        $_SESSION['user_id']
    ]);

    $invoiceId = $conn->lastInsertId();

      $conn->prepare("
        UPDATE po_bank_details
        SET is_active = 0
        WHERE po_id = ? AND is_active = 1
    ")->execute([$_POST['POId']]);

    $conn->prepare("
        INSERT INTO po_bank_details (
            po_id, pan_number, pfms_number,
            bank_name, ifsc, account_number,
            is_active, created_at
        )
        VALUES (?,?,?,?,?,?,1,GETDATE())
    ")->execute([
        $_POST['POId'],
        $_POST['PanNumber'] ?? null,
        $_POST['PFMSNumber'] ?? null,
        $_POST['BankName'] ?? null,
        $_POST['IFSC'] ?? null,
        $_POST['AccountNumber'] ?? null
    ]);
    /* ================= MAP SANCTIONS ================= */
    $mapStmt = $conn->prepare("
        INSERT INTO invoice_sanction_map
(
    InvoiceId,
    SanctionId,
    SanctionBaseAmount,
    GSTAmount,
    ITAmount,
    NetAmount
)
VALUES (?, ?, ?, ?, ?, ?)
    ");

    $remaining = $amount;

   foreach ($sanctionIds as $sid) {

    if ($remaining <= 0) break;

    $stmt = $conn->prepare("
        SELECT SanctionAmount, GSTPercent, ITPercent
        FROM sanction_order_master
        WHERE Id = ?
    ");
    $stmt->execute([$sid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get already used amount
    $usedStmt = $conn->prepare("
        SELECT ISNULL(SUM(SanctionBaseAmount),0)
        FROM invoice_sanction_map
        WHERE SanctionId = ?
    ");
    $usedStmt->execute([$sid]);
    $used = (float)$usedStmt->fetchColumn();

    $available = $row['SanctionAmount'] - $used;

    $baseUsed = min($available, $remaining);
    $remaining -= $baseUsed;

    $gstAmt = $baseUsed * $row['GSTPercent'] / 100;
    $itAmt  = $baseUsed * $row['ITPercent'] / 100;
    $netAmt = $baseUsed + $gstAmt + $itAmt;

    $mapStmt->execute([
        $invoiceId,
        $sid,
        $baseUsed,
        $gstAmt,
        $itAmt,
        $netAmt
    ]);
}
 $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {

    /* ================= ROLLBACK ================= */
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

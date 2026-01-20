<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try{

    $conn->beginTransaction();

    /* ================= PO CALCULATIONS ================= */
    $poAmount = (float) $_POST['POAmount'];
    $poGstP   = (float) $_POST['POGSTPercent'];
    $poItP    = (float) $_POST['POITPercent'];

    $poGstAmt = $poAmount * $poGstP / 100;
    $poItAmt  = $poAmount * $poItP / 100;
    $poNetAmt = $poAmount + $poGstAmt + $poItAmt;

    $chkPo = $conn->prepare("SELECT COUNT(*) FROM po_master WHERE POOrderNo = ?");
    $chkPo->execute([$_POST['PONumber']]);

    if ($chkPo->fetchColumn() > 0) {
        throw new Exception('PO Number already exists');
    }

    /* ================= SAVE PO MASTER ================= */
    $poStmt = $conn->prepare("
        INSERT INTO po_master
        (
            POOrderNo,
            POOrderDate,
            GSTNumber,
            POAmount,
            POGSTPercent,
            POITPercent,
            PONetAmount,
            CreatedBy
        )
        VALUES (?,?,?,?,?,?,?,?)
    ");

    $poStmt->execute([
        $_POST['PONumber'],
        $_POST['PODate'],
        $_POST['GSTNumber'],
        $poAmount,
        $poGstP,
        $poItP,
        $poNetAmt,
        $_SESSION['user_id']
    ]);

    $poId = $conn->lastInsertId();


    /* ================= SAVE BANK DETAILS ================= */
    $bankStmt = $conn->prepare("
        INSERT INTO po_bank_details
        (
            po_id,
            pan_number,
            pfms_number,
            bank_name,
            ifsc,
            account_number,
            created_at
        )
        VALUES (?,?,?,?,?,?,GETDATE())
    ");

    $bankStmt->execute([
        $poId,
        $_POST['PanNumber'] ?? null,
        $_POST['PFMSNumber'] ?? null,
        $_POST['BankName'] ?? null,
        $_POST['IFSC'] ?? null,
        $_POST['AccountNumber'] ?? null
    ]);

    /* ================= SAVE SANCTION ORDERS ================= */
    $sanStmt = $conn->prepare("
        INSERT INTO sanction_order_master
        (
            POId,
            SanctionOrderNo,
            SanctionDate,
            SanctionAmount,
            GSTPercent,
            GSTAmount,
            ITPercent,
            ITAmount,
            SanctionNetAmount,
            CreatedBy
        )
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");


    $sanctionNos = array_filter($_POST['SanctionNo']);
    if (count($sanctionNos) !== count(array_unique($sanctionNos))) {
        throw new Exception('Duplicate Sanction Numbers in the form');
    }

    for($i = 0; $i < count($_POST['SanctionNo']); $i++){

        if (empty($_POST['SanctionAmount'][$i])) continue;

        $amount = (float) $_POST['SanctionAmount'][$i];

        // GST & IT SAME AS PO (READONLY IN UI)
        $gstP = $poGstP;
        $itP  = $poItP;

        $gstAmt = $amount * $gstP / 100;
        $itAmt  = $amount * $itP / 100;
        $netAmt = $amount + $gstAmt + $itAmt;

        $sanStmt->execute([
            $poId,
            $_POST['SanctionNo'][$i],
            $_POST['SanctionDate'][$i],
            $amount,
            $gstP,
            $gstAmt,
            $itP,
            $itAmt,
            $netAmt,
            $_SESSION['user_id']
        ]);
    }

    $conn->commit();

    echo json_encode(['status'=>'success']);

}catch(Exception $e){
    $conn->rollBack();
    echo json_encode([
        'status'=>'error',
        'message'=>$e->getMessage()
    ]);
}

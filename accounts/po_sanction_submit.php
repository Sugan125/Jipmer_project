<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try{
    $conn->beginTransaction();

    // Duplicate PO check
    $chkPo = $conn->prepare("SELECT COUNT(*) FROM po_master WHERE POOrderNo = ?");
    $chkPo->execute([trim($_POST['PONumber'])]);
    if ($chkPo->fetchColumn() > 0) {
        throw new Exception('PO Number already exists');
    }

    /* ================= INSERT PO MASTER (no GST/IT % in master) ================= */
    $poStmt = $conn->prepare("
        INSERT INTO po_master
        (
            POOrderNo,
            POOrderDate,
            GSTNumber,
            POAmount,
            PONetAmount,
            CreatedBy
        )
        VALUES (?,?,?,?,?,?)
    ");

    // insert with 0 totals, update after items
    $poStmt->execute([
        trim($_POST['PONumber']),
        $_POST['PODate'],
        $_POST['GSTNumber'],
        0,
        0,
        $_SESSION['user_id']
    ]);

    $poId = (int)$conn->lastInsertId();

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

    /* ================= SAVE PO ITEMS (item-wise % ) ================= */
    if(
        !isset($_POST['ItemName'], $_POST['ItemAmount'], $_POST['ItemGSTPercent'], $_POST['ItemITPercent']) ||
        !is_array($_POST['ItemName']) || !is_array($_POST['ItemAmount']) ||
        !is_array($_POST['ItemGSTPercent']) || !is_array($_POST['ItemITPercent'])
    ){
        throw new Exception('PO items not received. Please add item rows.');
    }

    $itemStmt = $conn->prepare("
        INSERT INTO po_items
        (
            POId,
            ItemName,
            ItemAmount,
            GSTPercent,
            GSTAmount,
            ITPercent,
            ITAmount,
            NetAmount,
            CreatedDate
        )
        VALUES (?,?,?,?,?,?,?,?,GETDATE())
    ");

    $totalBase = 0;
    $totalNet  = 0;

    for($i=0; $i<count($_POST['ItemName']); $i++){
        $name = trim((string)($_POST['ItemName'][$i] ?? ''));
        $amt  = (float)($_POST['ItemAmount'][$i] ?? 0);
        $gp   = (float)($_POST['ItemGSTPercent'][$i] ?? 0);
        $ip   = (float)($_POST['ItemITPercent'][$i] ?? 0);

        if($name==='' && $amt<=0) continue;

        if($name==='') throw new Exception('Item Name is required.');
        if($amt<=0)   throw new Exception('Item Amount must be greater than 0.');

        if($gp < 0 || $gp > 100) throw new Exception('GST % must be between 0 and 100.');
        if($ip < 0 || $ip > 100) throw new Exception('IT % must be between 0 and 100.');

        $gstAmt = $amt * $gp / 100;
        $itAmt  = $amt * $ip / 100;
        $netAmt = $amt + $gstAmt + $itAmt;

        $itemStmt->execute([
            $poId,
            $name,
            $amt,
            $gp,
            $gstAmt,
            $ip,
            $itAmt,
            $netAmt
        ]);

        $totalBase += $amt;
        $totalNet  += $netAmt;
    }

    if($totalBase <= 0){
        throw new Exception('Please add at least one valid PO item.');
    }

    /* ================= SANCTION VALIDATION (TOTAL <= PO TOTAL) ================= */
    if(!isset($_POST['SanctionNo'], $_POST['SanctionDate'], $_POST['SanctionAmount']) ||
       !is_array($_POST['SanctionNo']) || !is_array($_POST['SanctionAmount'])){
        throw new Exception('Sanction details not received.');
    }

    // Duplicate sanction nos inside form
    $sanNos = array_filter(array_map('trim', $_POST['SanctionNo']));
    if(count($sanNos) !== count(array_unique($sanNos))){
        throw new Exception('Duplicate Sanction Numbers in the form');
    }

    $totalSanctionBase = 0;
    for($i=0; $i<count($_POST['SanctionNo']); $i++){
        $no  = trim((string)($_POST['SanctionNo'][$i] ?? ''));
        $dt  = $_POST['SanctionDate'][$i] ?? null;
        $amt = (float)($_POST['SanctionAmount'][$i] ?? 0);

        if($no==='' && $amt<=0) continue;

        if($no==='') throw new Exception('Sanction No is required.');
        if(empty($dt)) throw new Exception('Sanction Date is required.');
        if($amt<=0) throw new Exception('Sanction Amount must be greater than 0.');

        $totalSanctionBase += $amt;
    }

if($totalSanctionBase > $totalNet){
    throw new Exception('Total Sanction Amount exceeds PO Net Total (Item-wise Net).');
}
    /* ================= UPDATE PO MASTER TOTALS ================= */
    $updPo = $conn->prepare("
        UPDATE po_master
        SET POAmount = ?, PONetAmount = ?
        WHERE Id = ?
    ");
    $updPo->execute([$totalBase, $totalNet, $poId]);

    /* ================= SAVE SANCTIONS (NO GST/IT ENTRY NOW) ================= */
    // GST/IT will be saved as 0 in sanction_order_master
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

    for($i=0; $i<count($_POST['SanctionNo']); $i++){
        $no  = trim((string)($_POST['SanctionNo'][$i] ?? ''));
        $dt  = $_POST['SanctionDate'][$i] ?? null;
        $amt = (float)($_POST['SanctionAmount'][$i] ?? 0);

        if($no==='' && $amt<=0) continue;

        // store GST/IT as 0 (as per your request)
        $gstP = 0; $gstAmt = 0;
        $itP  = 0; $itAmt  = 0;
        $netAmt = $amt; // since gst/it is not used here

        $sanStmt->execute([
            $poId,
            $no,
            $dt,
            $amt,
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
    if($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

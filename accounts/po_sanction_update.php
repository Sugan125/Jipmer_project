<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

function toFloat($v){ return (float)($v ?? 0); }
function trimStr($v){ return trim((string)($v ?? '')); }

try{
    $poId = (int)($_POST['POId'] ?? 0);
    if($poId <= 0) throw new Exception('Invalid PO Id');

    $conn->beginTransaction();

    // verify PO exists
    $chk = $conn->prepare("SELECT COUNT(*) FROM po_master WHERE Id=?");
    $chk->execute([$poId]);
    if((int)$chk->fetchColumn() <= 0) throw new Exception('PO not found');

    $poNo = trimStr($_POST['PONumber']);
    $poDt = $_POST['PODate'] ?? null;
    $gstNo = trimStr($_POST['GSTNumber']);

    if($poNo === '') throw new Exception('PO Number is required');
    if(empty($poDt)) throw new Exception('PO Date is required');

    // PO No duplicate check excluding current
    $dup = $conn->prepare("SELECT COUNT(*) FROM po_master WHERE POOrderNo = ? AND Id <> ?");
    $dup->execute([$poNo, $poId]);
    if((int)$dup->fetchColumn() > 0) throw new Exception('PO Number already exists');

    /* ============== ITEMS VALIDATION + TOTALS ============== */
    $itemIds   = $_POST['ItemId'] ?? [];
    $itemNames = $_POST['ItemName'] ?? [];
    $itemAmts  = $_POST['ItemAmount'] ?? [];
    $itemGPs   = $_POST['ItemGSTPercent'] ?? [];
    $itemIPs   = $_POST['ItemITPercent'] ?? [];

    if(!is_array($itemNames) || count($itemNames) === 0){
        throw new Exception('PO items not received');
    }

    $totalBase = 0; $totalNet = 0;

    // prepare statements
    $insItem = $conn->prepare("
        INSERT INTO po_items (POId, ItemName, ItemAmount, GSTPercent, GSTAmount, ITPercent, ITAmount, NetAmount, CreatedDate)
        VALUES (?,?,?,?,?,?,?,?,GETDATE())
    ");
    $updItem = $conn->prepare("
        UPDATE po_items
        SET ItemName=?, ItemAmount=?, GSTPercent=?, GSTAmount=?, ITPercent=?, ITAmount=?, NetAmount=?
        WHERE Id=? AND POId=?
    ");

    $keepItemIds = [];

    for($i=0; $i<count($itemNames); $i++){
        $id   = (int)($itemIds[$i] ?? 0);
        $name = trimStr($itemNames[$i] ?? '');
        $amt  = toFloat($itemAmts[$i] ?? 0);
        $gp   = toFloat($itemGPs[$i] ?? 0);
        $ip   = toFloat($itemIPs[$i] ?? 0);

        if($name==='' && $amt<=0) continue;

        if($name==='') throw new Exception('Item Name is required');
        if($amt<=0) throw new Exception('Item Amount must be > 0');
        if($gp < 0 || $gp > 100) throw new Exception('GST% must be 0-100');
        if($ip < 0 || $ip > 100) throw new Exception('IT% must be 0-100');

        $gstAmt = $amt * $gp / 100;
        $itAmt  = $amt * $ip / 100;

        // If IT is deduction, change to: $netAmt = $amt + $gstAmt - $itAmt;
        $netAmt = $amt + $gstAmt + $itAmt;

        if($id > 0){
            $updItem->execute([$name,$amt,$gp,$gstAmt,$ip,$itAmt,$netAmt,$id,$poId]);
            $keepItemIds[] = $id;
        }else{
            $insItem->execute([$poId,$name,$amt,$gp,$gstAmt,$ip,$itAmt,$netAmt]);
            $keepItemIds[] = (int)$conn->lastInsertId();
        }

        $totalBase += $amt;
        $totalNet  += $netAmt;
    }

    if($totalBase <= 0) throw new Exception('Please add at least one valid PO item');

    /* ============== SANCTIONS VALIDATION ============== */
    $sanIds  = $_POST['SanctionId'] ?? [];
    $sanNos  = $_POST['SanctionNo'] ?? [];
    $sanDts  = $_POST['SanctionDate'] ?? [];
    $sanAmts = $_POST['SanctionAmount'] ?? [];

    if(!is_array($sanNos)) throw new Exception('Sanction details not received');

    // duplicates in form
    $tmpNos = [];
    foreach($sanNos as $sn){
        $sn = trimStr($sn);
        if($sn !== '') $tmpNos[] = $sn;
    }
    if(count($tmpNos) !== count(array_unique($tmpNos))){
        throw new Exception('Duplicate Sanction Numbers in the form');
    }

    $totalSanction = 0;

    $insSan = $conn->prepare("
        INSERT INTO sanction_order_master
        (POId, SanctionOrderNo, SanctionDate, SanctionAmount, GSTPercent, GSTAmount, ITPercent, ITAmount, SanctionNetAmount, CreatedBy)
        VALUES (?,?,?,?,0,0,0,0,?,?)
    ");
    $updSan = $conn->prepare("
        UPDATE sanction_order_master
        SET SanctionOrderNo=?, SanctionDate=?, SanctionAmount=?, SanctionNetAmount=?
        WHERE Id=? AND POId=?
    ");

    $keepSanIds = [];

    for($i=0; $i<count($sanNos); $i++){
        $id = (int)($sanIds[$i] ?? 0);
        $no = trimStr($sanNos[$i] ?? '');
        $dt = $sanDts[$i] ?? null;
        $amt = toFloat($sanAmts[$i] ?? 0);

        if($no==='' && $amt<=0) continue;

        if($no==='') throw new Exception('Sanction No is required');
        if(empty($dt)) throw new Exception('Sanction Date is required');
        if($amt<=0) throw new Exception('Sanction Amount must be > 0');

        $totalSanction += $amt;

        if($id > 0){
            $updSan->execute([$no,$dt,$amt,$amt,$id,$poId]);
            $keepSanIds[] = $id;
        }else{
            $insSan->execute([$poId,$no,$dt,$amt,$amt,$_SESSION['user_id']]);
            $keepSanIds[] = (int)$conn->lastInsertId();
        }
    }

    // ✅ compare against PO NET (same as your JS)
    if($totalSanction > $totalNet){
        throw new Exception('Total Sanction Amount exceeds PO Net Total (Item-wise Net).');
    }

    /* ============== DELETE REMOVED ITEMS/SANCTIONS ============== */
    // delete removed items
    if(count($keepItemIds) > 0){
        $in = implode(',', array_fill(0, count($keepItemIds), '?'));
        $del = $conn->prepare("DELETE FROM po_items WHERE POId=? AND Id NOT IN ($in)");
        $del->execute(array_merge([$poId], $keepItemIds));
    }else{
        $conn->prepare("DELETE FROM po_items WHERE POId=?")->execute([$poId]);
    }

    // delete removed sanctions
    if(count($keepSanIds) > 0){
        $in = implode(',', array_fill(0, count($keepSanIds), '?'));
        $del = $conn->prepare("DELETE FROM sanction_order_master WHERE POId=? AND Id NOT IN ($in)");
        $del->execute(array_merge([$poId], $keepSanIds));
    }else{
        $conn->prepare("DELETE FROM sanction_order_master WHERE POId=?")->execute([$poId]);
    }

    /* ============== UPDATE PO MASTER ============== */
    $updPo = $conn->prepare("
        UPDATE po_master
        SET POOrderNo=?, POOrderDate=?, GSTNumber=?, POAmount=?, PONetAmount=?
        WHERE Id=?
    ");
    $updPo->execute([$poNo,$poDt,$gstNo,$totalBase,$totalNet,$poId]);

    /* ============== UPSERT BANK DETAILS (latest row) ============== */
    $pan  = $_POST['PanNumber'] ?? null;
    $pfms = $_POST['PFMSNumber'] ?? null;
    $bnk  = $_POST['BankName'] ?? null;
    $ifsc = $_POST['IFSC'] ?? null;
    $acc  = $_POST['AccountNumber'] ?? null;

    // check existing row
    $bk = $conn->prepare("SELECT TOP 1 Id FROM po_bank_details WHERE po_id=? ORDER BY Id DESC");
    $bk->execute([$poId]);
    $bkId = (int)($bk->fetchColumn() ?? 0);

    if($bkId > 0){
        $ub = $conn->prepare("
            UPDATE po_bank_details
            SET pan_number=?, pfms_number=?, bank_name=?, ifsc=?, account_number=?
            WHERE Id=? AND po_id=?
        ");
        $ub->execute([$pan,$pfms,$bnk,$ifsc,$acc,$bkId,$poId]);
    }else{
        $ib = $conn->prepare("
            INSERT INTO po_bank_details (po_id, pan_number, pfms_number, bank_name, ifsc, account_number, created_at)
            VALUES (?,?,?,?,?,?,GETDATE())
        ");
        $ib->execute([$poId,$pan,$pfms,$bnk,$ifsc,$acc]);
    }

    $conn->commit();
    echo json_encode(['status'=>'success']);

}catch(Exception $e){
    if($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

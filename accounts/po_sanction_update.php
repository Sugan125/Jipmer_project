<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try{
$conn->beginTransaction();

$poId = (int)$_POST['POId'];
$a = (float)$_POST['POAmount'];
$gp = (float)$_POST['POGSTPercent'];
$ip = (float)$_POST['POITPercent'];
$g = $a*$gp/100;
$i = $a*$ip/100;
$net = $a+$g+$i;

/* UPDATE PO */
$stmt = $conn->prepare("
UPDATE po_master SET
POOrderNo=?, POOrderDate=?, POAmount=?, POGSTPercent=?, POITPercent=?, PONetAmount=?
WHERE Id=?
");
$stmt->execute([
$_POST['PONumber'],$_POST['PODate'],$a,$gp,$ip,$net,$poId
]);

 $check = $conn->prepare("
    SELECT COUNT(*) 
    FROM po_bank_details 
    WHERE po_id = ? AND is_active = 1
");
$check->execute([$poId]);


if ($check->fetchColumn() > 0) {

    // deactivate old record
    $conn->prepare("
        UPDATE po_bank_details 
        SET is_active = 0 
        WHERE po_id = ? AND is_active = 1
    ")->execute([$poId]);
}

/* insert fresh active bank record */
$stmtBank = $conn->prepare("
    INSERT INTO po_bank_details
    (
        po_id,
        pan_number,
        pfms_number,
        bank_name,
        ifsc,
        account_number,
        is_active,
        created_at
    )
    VALUES (?,?,?,?,?,?,1,GETDATE())
");

$stmtBank->execute([
    $poId,
    $_POST['PanNumber'] ?? null,
    $_POST['PFMSNumber'] ?? null,
    $_POST['BankName'] ?? null,
    $_POST['IFSC'] ?? null,
    $_POST['AccountNumber'] ?? null
]);

/* DELETE OLD SANCTIONS */
$conn->prepare("DELETE FROM sanction_order_master WHERE POId=?")->execute([$poId]);

/* INSERT SANCTIONS */
$ins = $conn->prepare("
INSERT INTO sanction_order_master
(POId,SanctionOrderNo,SanctionDate,SanctionAmount,GSTPercent,GSTAmount,ITPercent,ITAmount,SanctionNetAmount,CreatedBy)
VALUES (?,?,?,?,?,?,?,?,?,?)
");

for($i=0;$i<count($_POST['SanctionNo']);$i++){
 if(empty($_POST['SanctionAmount'][$i])) continue;
 $amt=(float)$_POST['SanctionAmount'][$i];
 $gsa=$amt*$gp/100;
 $ita=$amt*$ip/100;
 $ins->execute([
  $poId,
  $_POST['SanctionNo'][$i],
  $_POST['SanctionDate'][$i],
  $amt,
  $gp,$gsa,
  $ip,$ita,
  $amt+$gsa+$ita,
  $_SESSION['user_id']
 ]);
}

$conn->commit();
echo json_encode(['status'=>'success']);

}catch(Exception $e){
$conn->rollBack();
echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

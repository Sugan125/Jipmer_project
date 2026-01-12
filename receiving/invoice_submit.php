<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';
header('Content-Type: application/json');

try{
$gstAmt = $_POST['Amount'] * $_POST['GSTPercent'] / 100;
$itAmt  = $_POST['Amount'] * $_POST['ITPercent'] / 100;

$totalAmount = $_POST['Amount'] + $gstAmt + $itAmt;

$tdsG = $_POST['Amount'] * $_POST['TDSGSTPercent'] / 100;
$tdsI = $_POST['Amount'] * $_POST['TDSITPercent'] / 100;
//$sanctionIds = $_POST['SanctionId']; 
$check = $conn->prepare("
    SELECT COUNT(*) 
    FROM invoice_master 
    WHERE SanctionId = ?
");

// foreach ($sanctionIds as $sid) {
//     $check->execute([$sid]);
//     if ($check->fetchColumn() > 0) {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'One of the selected sanctions is already billed'
//         ]);
//         exit;
//     }
// }

$stmtsanction = $conn->prepare("
    SELECT SanctionNetAmount 
    FROM sanction_order_master 
    WHERE Id = ?
");
// foreach ($sanctionIds as $sid) {
//     $stmtsanction->execute([$sid]);
//     $totalSanctionBalance += (float)$stmtsanction->fetchColumn();
// }

// if ($_POST['Amount'] > $totalSanctionBalance) {
//     echo json_encode([
//         'status' => 'error',
//         'message' => 'Invoice amount exceeds combined sanction balance'
//     ]);
//     exit;
// }

$netPayable = $totalAmount - ($tdsG + $tdsI);

$stmt = $conn->prepare("
INSERT INTO invoice_master
(
 FinancialYearId, HOAId, DeptId, BillTypeId,
 CreditToId, DebitFromId,

 InvoiceNo, InvoiceDate, VendorName,

 POId, SanctionId,

 BankName, IFSC, AccountNumber, PanNumber,
 ReceivedFromSection, SectionDAName,

 Amount, GSTPercent, GSTAmount,
 ITPercent, ITAmount,
 TotalAmount,

 TDSGSTPercent, TDSGSTAmount,
 TDSITPercent, TDSITAmount,
 NetPayable,

 CreatedBy
)
VALUES (
 ?,?,?,?,
 ?,?,
 ?,?,?,
 ?,?,
 ?,?,?,?,
 ?,?,
 ?,?,?,
 ?,?,
 ?,
 ?,?,
 ?,?,
 ?,
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
 //$sid,
  $_POST['SanctionId'],

 $_POST['BankName'],
 $_POST['IFSC'],
 $_POST['AccountNumber'],
 $_POST['PanNumber'],
 $_POST['ReceivedFromSection'],
 $_POST['SectionDAName'],

 $_POST['Amount'],
 $_POST['GSTPercent'],
 $gstAmt,

 $_POST['ITPercent'],
 $itAmt,
 $totalAmount,

 $_POST['TDSGSTPercent'],
 $tdsG,

 $_POST['TDSITPercent'],
 $tdsI,

 $netPayable,

 $_SESSION['user_id']
]);



echo json_encode(['status'=>'success']);
}catch(Exception $e){
 echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
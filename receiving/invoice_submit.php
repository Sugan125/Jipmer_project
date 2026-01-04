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

$netPayable = $totalAmount - ($tdsG + $tdsI);

$stmt = $conn->prepare("
INSERT INTO invoice_master
(
 FinancialYearId, HOAId, DeptId, BillTypeId, CreditToId, DebitFromId,

 InvoiceNo, InvoiceDate, VendorName,

 SanctionOrderNo, SanctionDate,
 BankName, IFSC, AccountNumber,
 ReceivedFromSection, SectionDAName,
 PFMSUniqueNo,
 POOrderNo, POOrderDate,

 Amount, GSTPercent, GSTAmount,
 ITPercent, ITAmount,
 TotalAmount,

 TDSGSTPercent, TDSGSTAmount,
 TDSITPercent, TDSITAmount,
 NetPayable,

 POAmount, POGSTPercent, POGSTAmount,
 POITPercent, POITAmount,

 TDSPoGSTPercent, TDSPoGSTAmount,
 TDSPoITPercent, TDSPoITAmount,

 CreatedBy
)
VALUES (
 ?,?,?,?,?,?,
 ?,?,?,
 ?,?,
 ?,?,?,
 ?,?,
 ?,
 ?,?,
 ?,?,?,
 ?,?,
 ?,
 ?,?,
 ?,?,
 ?,
?,?,?,
 ?,?,
 ?,?,
 ?,?,
 ?
)
");


$stmt->execute([
 $_POST['FinancialYearId'], $_POST['HOAId'], $_POST['DeptId'], $_POST['BillTypeId'],
 $_POST['CreditToId'], $_POST['DebitFromId'],

 $_POST['InvoiceNo'], $_POST['InvoiceDate'], $_POST['VendorName'],

 $_POST['SanctionOrderNo'], $_POST['SanctionDate'],
 $_POST['BankName'], $_POST['IFSC'], $_POST['AccountNumber'],
 $_POST['ReceivedFromSection'], $_POST['SectionDAName'],
 $_POST['PFMSUniqueNo'],
 $_POST['POOrderNo'], $_POST['POOrderDate'],

 $_POST['Amount'], $_POST['GSTPercent'], $gstAmt,
 $_POST['ITPercent'], $itAmt,
 $totalAmount,

 $_POST['TDSGSTPercent'], $tdsG,
 $_POST['TDSITPercent'], $tdsI,
 $netPayable,

 $_POST['POAmount'], $_POST['POGSTPercent'],
 $_POST['POAmount'] * $_POST['POGSTPercent'] / 100,

 $_POST['POITPercent'],
 $_POST['POAmount'] * $_POST['POITPercent'] / 100,

 $_POST['TDSPoGSTPercent'],
 $_POST['POAmount'] * $_POST['TDSPoGSTPercent'] / 100,

 $_POST['TDSPoITPercent'],
 $_POST['POAmount'] * $_POST['TDSPoITPercent'] / 100,

 $_SESSION['user_id']
]);



echo json_encode(['status'=>'success']);
}catch(Exception $e){
 echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
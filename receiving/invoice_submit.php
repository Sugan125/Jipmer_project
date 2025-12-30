<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';
header('Content-Type: application/json');

try{
$stmt=$conn->prepare("INSERT INTO invoice_master
(FinancialYearId,HOAId,DeptId,BillTypeId,CreditToId,DebitFromId,
InvoiceNo,InvoiceDate,VendorName,SanctionOrderNo,SanctionDate,
Amount,GSTPercent,GSTAmount,ITPercent,ITAmount,TDS,TotalAmount,
BankName,IFSC,AccountNumber,ReceivedFromSection,SectionDAName,
PFMSUniqueNo,POOrderNo,POOrderDate,CreatedBy)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$gstAmt=$_POST['Amount']*$_POST['GSTPercent']/100;
$itAmt=$_POST['Amount']*$_POST['ITPercent']/100;

$stmt->execute([
    $_POST['FinancialYearId'], $_POST['HOAId'], $_POST['DeptId'], $_POST['BillTypeId'],
    $_POST['CreditToId'], $_POST['DebitFromId'], $_POST['InvoiceNo'], $_POST['InvoiceDate'], 
    $_POST['VendorName'], $_POST['SanctionOrderNo'], $_POST['SanctionDate'], 
    $_POST['Amount'], $_POST['GSTPercent'], $gstAmt, $_POST['ITPercent'], $itAmt, 
    $_POST['TDS'], $_POST['TotalAmount'], $_POST['BankName'], $_POST['IFSC'], 
    $_POST['AccountNumber'], $_POST['ReceivedFromSection'], $_POST['SectionDAName'], 
    $_POST['PFMSUniqueNo'], $_POST['POOrderNo'], $_POST['POOrderDate'], $_SESSION['user_id']
]);

echo json_encode(['status'=>'success']);
}catch(Exception $e){
 echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
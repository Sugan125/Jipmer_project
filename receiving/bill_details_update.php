<?php
include '../config/db.php';
include '../includes/auth.php';
header('Content-Type: application/json');

if(!isset($_POST['BillId'])) exit(json_encode(['status'=>'error','message'=>'Bill ID missing']));

$billId = intval($_POST['BillId']);

try{
    $conn->beginTransaction();

    // Update bill_initial_entry
    $stmt = $conn->prepare("
        UPDATE bill_initial_entry
        SET BillNumber=?, BillReceivedDate=?, ReceivedFromSection=?, SectionDAName=?,
            BillTypeId=?, POOrderNo=?, POOrderDate=?, IT=?, GST=?, TDS_Type=?, PFMSUniqueNo=?
        WHERE Id=?
    ");
    $stmt->execute([
        $_POST['BillNumber'],
        $_POST['BillReceivedDate'],
        $_POST['ReceivedFromSection'],
        $_POST['SectionDAName'],
        $_POST['BillTypeId'],
        $_POST['PONumber'],
        $_POST['PODate'],
        $_POST['IT'],
        $_POST['GST'],
        $_POST['TDSType'],
        $_POST['PFMSUniqueNo'],
        $billId
    ]);

    // Update invoice mapping
    $conn->prepare("DELETE FROM bill_invoice_map WHERE BillInitialId=?")->execute([$billId]);
    if(!empty($_POST['Invoices'])){
        $stmtInv = $conn->prepare("INSERT INTO bill_invoice_map (BillInitialId, InvoiceId) VALUES (?,?)");
        foreach($_POST['Invoices'] as $inv){
            $stmtInv->execute([$billId,$inv]);
        }
    }

    $conn->commit();
    echo json_encode(['status'=>'success']);
}catch(Exception $e){
    $conn->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

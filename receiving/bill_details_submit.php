<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try{
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO bill_initial_entry
        (BillNumber, BillReceivedDate, ReceivedFromSection, SectionDAName,
         BillTypeId, POOrderNo, POOrderDate, IT, GST, TDS_Type, PFMSUniqueNo,
         CreatedBy, CreatedDate, Status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,GETDATE(), 'DRAFT')
    ");

    $stmt->execute([
        $_POST['BillNumber'],
        $_POST['BillReceivedDate'],
        $_POST['ReceivedFromSection'],
        $_POST['SectionDAName'],
        $_POST['BillTypeId'],
        $_POST['POOrderNo'],
        $_POST['POOrderDate'],
        $_POST['IT'],
        $_POST['GST'],
        $_POST['TDSType'],
        $_POST['PFMSUniqueNo'],
        $_SESSION['user_id']
    ]);

   $billId = $conn->lastInsertId();

/* Attach invoices */
if (!empty($_POST['Invoices'])) {
    $stmtInv = $conn->prepare("
        INSERT INTO bill_invoice_map (BillInitialId, InvoiceId)
        VALUES (?,?)
    ");
    foreach ($_POST['Invoices'] as $inv) {
        $stmtInv->execute([$billId, $inv]);
    }
}
    $conn->commit();
    echo json_encode(['status'=>'success']);

}catch(Exception $e){
    $conn->rollBack();
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

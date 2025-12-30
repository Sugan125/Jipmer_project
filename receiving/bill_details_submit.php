<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['BillNumber']) || empty($_POST['BillReceivedDate'])) {
        throw new Exception("Bill Number and Bill Received Date are required.");
    }

    $conn->beginTransaction();

    $totalAmount = 0;
    $totalGST = 0;
    $totalTDS = 0;
    $grossTotal = 0;
    $netTotal = 0;

    $invoiceIds = $_POST['Invoices'] ?? [];

    if (!empty($invoiceIds)) {
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));

        $stmtInv = $conn->prepare("
            SELECT TotalAmount, GSTAmount, TDS
            FROM invoice_master
            WHERE Id IN ($placeholders)
        ");
        $stmtInv->execute($invoiceIds);
        $selectedInvoices = $stmtInv->fetchAll(PDO::FETCH_ASSOC);

        foreach ($selectedInvoices as $inv) {
            $totalAmount += $inv['TotalAmount'];
            $totalGST += $inv['GSTAmount'];
            $totalTDS += $inv['TDS'];
        }

        $grossTotal = $totalAmount + $totalGST;
        $netTotal = $grossTotal - $totalTDS;
    }

    // Insert into bill_initial_entry with totals
    $stmt = $conn->prepare("
        INSERT INTO bill_initial_entry
        (BillNumber, BillReceivedDate, CreatedBy, CreatedDate, Status, TotalAmount, TotalGST, TotalTDS, GrossTotal, NetTotal)
        VALUES (?, ?, ?, GETDATE(), 'DRAFT', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['BillNumber'],
        $_POST['BillReceivedDate'],
        $_SESSION['user_id'],
        $totalAmount,
        $totalGST,
        $totalTDS,
        $grossTotal,
        $netTotal
    ]);

    $billId = $conn->lastInsertId();

    // Attach selected invoices
    if (!empty($invoiceIds)) {
        $stmtMap = $conn->prepare("INSERT INTO bill_invoice_map (BillInitialId, InvoiceId) VALUES (?, ?)");
        foreach ($invoiceIds as $invId) {
            $stmtMap->execute([$billId, $invId]);
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
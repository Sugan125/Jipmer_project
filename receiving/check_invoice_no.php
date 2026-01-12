<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$invoiceNo = trim($_GET['InvoiceNo'] ?? '');

if ($invoiceNo === '') {
    echo json_encode(['exists' => false]);
    exit;
}

/* FOR EDIT PAGE (optional) */
$invoiceId = $_GET['InvoiceId'] ?? 0;

$sql = "
    SELECT COUNT(*) 
    FROM invoice_master 
    WHERE InvoiceNo = ?
";

$params = [$invoiceNo];

if ($invoiceId) {
    $sql .= " AND Id <> ?";
    $params[] = $invoiceId;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$exists = $stmt->fetchColumn() > 0;

echo json_encode(['exists' => $exists]);

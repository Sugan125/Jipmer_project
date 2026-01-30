<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$poId = (int)($_GET['POId'] ?? 0);
if($poId <= 0){
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        Id,
        ItemName,
        ISNULL(ItemAmount,0) AS ItemAmount,
        ISNULL(GSTPercent,0) AS GSTPercent,
        ISNULL(GSTAmount,0)  AS GSTAmount,
        ISNULL(ITPercent,0)  AS ITPercent,
        ISNULL(ITAmount,0)   AS ITAmount,
        ISNULL(NetAmount,0)  AS NetAmount
    FROM po_items
    WHERE POId = ?
    ORDER BY Id ASC
");
$stmt->execute([$poId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

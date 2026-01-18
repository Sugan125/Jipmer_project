<?php
include '../config/db.php';

$stmt = $conn->query("
    SELECT 
        Id,
        POOrderNo,
        POOrderDate,
        POAmount,
        POGSTPercent,
        POITPercent,
        PONetAmount
    FROM po_master
    ORDER BY Id DESC
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
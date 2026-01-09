<?php
include '../config/db.php';

$poId = (int)$_GET['POId'];

$stmt = $conn->prepare("
    SELECT Id, SanctionOrderNo, SanctionNetAmount
    FROM sanction_order_master
    WHERE POId = ?
");
$stmt->execute([$poId]);

echo json_encode($stmt->fetchAll());

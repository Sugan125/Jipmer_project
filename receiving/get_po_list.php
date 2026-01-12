<?php
include '../config/db.php';

$stmt = $conn->query("
    SELECT Id, POOrderNo, PoNetAmount as balance
    FROM po_master
    ORDER BY Id DESC
");

echo json_encode($stmt->fetchAll());

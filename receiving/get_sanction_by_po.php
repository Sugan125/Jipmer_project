<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$poId = (int)$_GET['POId'];

$sql = "
SELECT
    s.Id,
    s.SanctionOrderNo,
    s.SanctionDate,
    s.SanctionAmount,
    s.GSTPercent,
    s.GSTAmount,
    s.ITPercent,
    s.ITAmount,
    s.SanctionNetAmount,
    s.SanctionAmount AS balance
FROM sanction_order_master s
WHERE s.POId = ?
AND NOT EXISTS (
    SELECT 1
    FROM invoice_master i
    WHERE i.SanctionId = s.Id
)";
$stmt = $conn->prepare($sql);
$stmt->execute([$poId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

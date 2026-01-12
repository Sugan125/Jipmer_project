<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$poId = (int)$_GET['POId'];

$sql = "
SELECT
    s.Id,
    s.SanctionOrderNo,
    s.SanctionNetAmount,
    s.SanctionNetAmount - ISNULL(SUM(i.NetPayable),0) AS balance
FROM sanction_order_master s
LEFT JOIN invoice_master i
    ON i.SanctionId = s.Id
WHERE s.POId = ?
GROUP BY s.Id, s.SanctionOrderNo, s.SanctionNetAmount
HAVING (s.SanctionNetAmount - ISNULL(SUM(i.NetPayable),0)) > 0
";

$stmt = $conn->prepare($sql);
$stmt->execute([$poId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

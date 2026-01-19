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
    s.ITPercent,
    s.SanctionNetAmount,

    ISNULL(used.used_amount, 0) AS used_amount,

    (s.SanctionAmount - ISNULL(used.used_amount, 0)) AS balance

FROM sanction_order_master s

LEFT JOIN (
    SELECT 
        SanctionId,
        SUM(SanctionBaseAmount) AS used_amount
    FROM invoice_sanction_map
    GROUP BY SanctionId
) used ON used.SanctionId = s.Id

WHERE s.POId = ?
AND (s.SanctionAmount - ISNULL(used.used_amount, 0)) > 0

ORDER BY s.SanctionOrderNo
";

$stmt = $conn->prepare($sql);
$stmt->execute([$poId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

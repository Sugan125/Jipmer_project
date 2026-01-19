<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['POId'])) {
    echo json_encode(['error' => 'POId missing']);
    exit;
}

$poId = (int)$_GET['POId'];

$sql = "
SELECT
    SUM(s.SanctionAmount) AS total_sanction,
    SUM(ISNULL(b.used_amount, 0)) AS billed_amount,
    SUM(s.SanctionAmount - ISNULL(b.used_amount, 0)) AS available_balance
FROM sanction_order_master s
LEFT JOIN (
    SELECT
        SanctionId,
        SUM(SanctionBaseAmount) AS used_amount
    FROM invoice_sanction_map
    GROUP BY SanctionId
) b ON b.SanctionId = s.Id
WHERE s.POId = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$poId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row);

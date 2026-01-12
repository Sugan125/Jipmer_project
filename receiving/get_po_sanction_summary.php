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
    ISNULL(SUM(s.SanctionNetAmount),0) AS total_sanction,
    ISNULL(SUM(i.Amount),0) AS billed_amount,
    ISNULL(SUM(s.SanctionNetAmount),0) - ISNULL(SUM(i.NetPayable),0) AS available_balance
FROM sanction_order_master s
LEFT JOIN invoice_master i
    ON i.SanctionId = s.Id
WHERE s.POId = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$poId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row);

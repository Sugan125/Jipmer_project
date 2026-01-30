<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$sql = "
SELECT
    p.Id,
    p.POOrderNo,
    CONVERT(varchar(10), p.POOrderDate, 23) AS POOrderDate,
    ISNULL(p.POAmount,0) AS POAmount,
    ISNULL(p.PONetAmount,0) AS PONetAmount,

    -- Sum GST/IT amounts from items
    ISNULL(pi.SumGST,0) AS SumGST,
    ISNULL(pi.SumIT,0)  AS SumIT,

    -- Derived overall % (for display)
    CASE WHEN ISNULL(p.POAmount,0) > 0 THEN ROUND((ISNULL(pi.SumGST,0) * 100.0) / p.POAmount, 2) ELSE 0 END AS POGSTPercent,
    CASE WHEN ISNULL(p.POAmount,0) > 0 THEN ROUND((ISNULL(pi.SumIT,0)  * 100.0) / p.POAmount, 2) ELSE 0 END AS POITPercent

FROM po_master p
LEFT JOIN (
    SELECT
        POId,
        SUM(ISNULL(GSTAmount,0)) AS SumGST,
        SUM(ISNULL(ITAmount,0))  AS SumIT
    FROM po_items
    GROUP BY POId
) pi ON pi.POId = p.Id
ORDER BY p.Id DESC
";

$stmt = $conn->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

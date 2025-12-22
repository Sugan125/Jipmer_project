<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../includes/auth.php';

$fy = $_GET['fy'] ?? '';
if($fy === '') {
    echo json_encode([]);
    exit;
}

// Select from hoa_master
$stmt = $conn->prepare("
    SELECT 
        HoaId AS Id, 
        CONCAT(DetailsHeadCode, ' - ', ObjectHeadCode, ' - ', SubDetailsHeadName) AS FullHOA,
        FinYearId
    FROM hoa_master
    WHERE FinYearId = ?
    ORDER BY DetailsHeadCode, ObjectHeadCode, SubDetailsHeadName
");
$stmt->execute([$fy]);
$hoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($hoas);

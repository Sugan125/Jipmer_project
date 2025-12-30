<?php
include '../config/db.php';

$finYearId = $_GET['FinYearId'] ?? 0;

$stmt = $conn->prepare("
    SELECT HoaId,
           DetailsHeadCode,
           DetailsHeadName,
           ObjectHeadCode,
           SubDetailsHeadName
    FROM hoa_master
    WHERE Status = 1
      AND FinYearId = ?
    ORDER BY DetailsHeadCode
");
$stmt->execute([$finYearId]);

$data = [];
while($row = $stmt->fetch()){
    $data[] = [
        'id' => $row['HoaId'],
        'text' => $row['DetailsHeadCode'].' - '.
                  $row['DetailsHeadName'].' / '.
                  $row['ObjectHeadCode'].' - '.
                  $row['SubDetailsHeadName']
    ];
}

echo json_encode($data);

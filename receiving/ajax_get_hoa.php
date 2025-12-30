<?php
include '../config/db.php';

$fy = $_GET['finYearId'];

$stmt = $conn->prepare("
    SELECT HoaId,
           DetailsHeadCode + ' - ' + DetailsHeadName + ' / ' + SubDetailsHeadName AS HOA_NAME
    FROM hoa_master
    WHERE Status=1 AND FinYearId=?
");
$stmt->execute([$fy]);

while($r = $stmt->fetch()){
    echo "<option value='{$r['HoaId']}'>{$r['HOA_NAME']}</option>";
}

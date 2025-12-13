<?php
header('Content-Type: application/json');
include '../config/db.php';
include '../includes/auth.php';
require_role(2);

$fy = $_GET['fy'] ?? '';
if($fy === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT Id, FullHOA FROM head_of_account_master WHERE FinancialYear = ? ORDER BY FullHOA");
$stmt->execute([$fy]);
$hoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($hoas);

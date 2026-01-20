<?php
include '../config/db.php';

$hoaId = $_GET['HOAId'] ?? 0;

$stmt = $conn->prepare("
    SELECT EcrNo, EcrDate
    FROM hoa_master
    WHERE HoaId = ?
");
$stmt->execute([$hoaId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($data ?: []);

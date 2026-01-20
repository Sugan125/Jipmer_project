<?php
include '../config/db.php';

$poId = $_GET['POId'] ?? 0;

$stmt = $conn->prepare("
    SELECT TOP 1 *
    FROM po_bank_details
    WHERE po_id = ?
      AND is_active = 1
    ORDER BY id DESC
");
$stmt->execute([$poId]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);

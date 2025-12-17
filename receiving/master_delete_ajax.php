<?php
session_start();
include '../config/db.php';

if ($_SESSION['role'] != 5) {
    http_response_code(403);
    exit;
}

$id   = $_POST['id'] ?? '';
$type = $_POST['type'] ?? '';

$map = [
    'bill'   => 'bill_type_master',
    'credit' => 'account_credit_master',
    'debit'  => 'account_debit_master'
];

if (!isset($map[$type]) || !$id) {
    http_response_code(400);
    exit;
}

$table = $map[$type];

/* SOFT DELETE (recommended) */
$stmt = $conn->prepare("UPDATE $table SET Status = 0 WHERE Id = ?");
$stmt->execute([$id]);

echo json_encode(['status' => 'success']);

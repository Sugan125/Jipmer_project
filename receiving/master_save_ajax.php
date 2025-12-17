<?php
session_start();
include '../config/db.php';

if ($_SESSION['role'] != 5) {
    http_response_code(403);
    exit;
}

$userId = $_SESSION['user_id']; // make sure this exists

$id   = $_POST['id'] ?? '';
$type = $_POST['type'];
$name = trim($_POST['name']);

$map = [
    'bill'   => ['bill_type_master', 'BillType'],
    'credit' => ['account_credit_master', 'CreditName'],
    'debit'  => ['account_debit_master', 'DebitName']
];

if (!isset($map[$type])) {
    http_response_code(400);
    exit;
}

[$table, $col] = $map[$type];

if ($id) {

    /* UPDATE */
    $stmt = $conn->prepare("
        UPDATE $table
        SET $col = ?
        WHERE Id = ?
    ");
    $stmt->execute([$name, $id]);

} else {

    /* INSERT */
    $stmt = $conn->prepare("
        INSERT INTO $table ($col, Status, CreatedBy)
        VALUES (?, 1, ?)
    ");
    $stmt->execute([$name, $userId]);
}

echo json_encode(['status' => 'success']);

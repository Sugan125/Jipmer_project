<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) exit;

try {
    $stmt = $conn->prepare("DELETE FROM account_debit_master WHERE Id=?");
    $stmt->execute([$id]);
} catch (PDOException $e) {
    exit("Error deleting debit: ".$e->getMessage());
}

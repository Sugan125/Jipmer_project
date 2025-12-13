<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if (!isset($_POST['id'])) {
    echo "error";
    exit;
}

$id = intval($_POST['id']);

$stmt = $conn->prepare("DELETE FROM head_of_account_master WHERE Id = ?");
if ($stmt->execute([$id])) {
    echo "success";
} else {
    echo "error";
}

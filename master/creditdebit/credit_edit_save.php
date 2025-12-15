<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['CreditName'] ?? '');
    $status = isset($_POST['Status']) && $_POST['Status'] == 1 ? 1 : 0;

    if ($id <= 0 || empty($name)) {
        echo json_encode(['status'=>'error','message'=>'Invalid input']); exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE account_credit_master SET CreditName=?, Status=? WHERE Id=?");
        $stmt->execute([$name, $status, $id]);
        echo json_encode(['status'=>'success','message'=>'Credit updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error','message'=>'Database error: '.$e->getMessage()]);
    }
}

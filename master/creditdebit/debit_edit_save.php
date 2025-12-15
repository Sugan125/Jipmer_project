<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['DebitName'] ?? '');
    $status = isset($_POST['Status']) && $_POST['Status'] == 1 ? 1 : 0;

    if ($id <= 0 || empty($name)) {
        echo json_encode(['status'=>'error','message'=>'Invalid input']); exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE account_debit_master SET DebitName=?, Status=?, UpdatedBy=?, UpdatedDate=GETDATE() WHERE Id=?");
        $stmt->execute([$name, $status, $_SESSION['user_id'], $id]);
        echo json_encode(['status'=>'success','message'=>'Debit updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status'=>'error','message'=>'Database error: '.$e->getMessage()]);
    }
}

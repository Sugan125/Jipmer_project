<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['DebitName']);
    $status = isset($_POST['Status']) && $_POST['Status'] == 1 ? 1 : 0;
    if($name !== ''){
        $stmt = $conn->prepare("INSERT INTO account_debit_master (DebitName, Status, CreatedBy) VALUES (?, ?, ?)");
        $stmt->execute([$name, $status, $_SESSION['user_id']]);
    }
}

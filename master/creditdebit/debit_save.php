<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['DebitName']);
    if($name !== ''){
        $stmt = $conn->prepare("INSERT INTO account_debit_master (DebitName, CreatedBy) VALUES (?, ?)");
        $stmt->execute([$name, $_SESSION['user_id']]);
    }
}
header('Location: debit_master.php');
exit;

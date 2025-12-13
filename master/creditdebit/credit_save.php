<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['CreditName']);
    if($name !== ''){
        $stmt = $conn->prepare("INSERT INTO account_credit_master (CreditName, CreatedBy) VALUES (?, ?)");
        $stmt->execute([$name, $_SESSION['user_id']]);
    }
}
header('Location: credit_master.php');
exit;

<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$id = intval($_GET['id']);
$conn->prepare("DELETE FROM account_credit_master WHERE Id=?")->execute([$id]);
header('Location: credit_master.php');

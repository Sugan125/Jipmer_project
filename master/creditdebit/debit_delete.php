<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$id = intval($_GET['id']);
$conn->prepare("DELETE FROM account_debit_master WHERE Id=?")->execute([$id]);
header('Location: debit_master.php');

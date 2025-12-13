<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json'); // important

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['status'=>'error','message'=>'Please enter username and password']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM employee_master WHERE Username=?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $password === $user['Password']) {
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['RoleId'];
    $_SESSION['user_id'] = $user['Id'];

    $redirect = '';
    if($user['RoleId'] == 1) $redirect = "../dashboard/dashboard_receiving.php";
    elseif($user['RoleId'] == 2) $redirect = "../dashboard/dashboard_processing.php";
    elseif($user['RoleId'] == 3) $redirect = "../dashboard/dashboard_accounts.php";
    elseif($user['RoleId'] == 4) $redirect = "../dashboard/dashboard_admin.php";

    echo json_encode(['status'=>'success','redirect'=>$redirect]);
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid username or password']);
}

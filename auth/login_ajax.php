<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter username and password'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT * 
    FROM employee_master 
    WHERE Username = ? AND Status = 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ⚠️ Plain password check (ok for now) */
if ($user && $password === $user['Password']) {

    $_SESSION['user_id'] = $user['Id'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['empname'] = $user['EmployeeName'];
    $_SESSION['role'] = $user['RoleId'];

    // ✅ ALWAYS redirect to one dashboard
    echo json_encode([
        'status'   => 'success',
        'redirect' => '../dashboard/dashboard.php'
    ]);
    exit;

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid username or password'
    ]);
}

<?php
session_start();
include '../../config/db.php';
header('Content-Type: application/json');



// Primary Key
$id = intval($_POST['Id'] ?? 0);

// Employee Code
$empCode = trim($_POST['EmpCode'] ?? '');

// Fields
$name  = trim($_POST['EmployeeName'] ?? '');
$username = trim($_POST['Username'] ?? '');
$password = trim($_POST['Password'] ?? ''); // optional
$roleId = intval($_POST['RoleId'] ?? 0);

// Required Field Validation
if($empCode=='' || $name=='' || $username=='' || $roleId==0){
    echo json_encode(['status'=>'error','message'=>'Please fill all required fields']);
    exit;
}

// Check username uniqueness (exclude current user)
$chk = $conn->prepare("SELECT COUNT(*) FROM employee_master WHERE Username = ? AND Id != ?");
$chk->execute([$username,$id]);
if($chk->fetchColumn() > 0){
    echo json_encode(['status'=>'error','message'=>'Username already exists']);
    exit;
}

try {

    if($password !== ''){
        // Password should be hashed in production
        $stmt = $conn->prepare("
            UPDATE employee_master 
            SET EmpCode = ?, EmployeeName = ?, Username = ?, Password = ?, RoleId = ? 
            WHERE Id = ?
        ");
        $stmt->execute([$empCode, $name, $username, $password, $roleId, $id]);
    } else {
        $stmt = $conn->prepare("
            UPDATE employee_master 
            SET EmpCode = ?, EmployeeName = ?, Username = ?, RoleId = ? 
            WHERE Id = ?
        ");
        $stmt->execute([$empCode, $name, $username, $roleId, $id]);
    }

    // Fetch updated role name
    $r = $conn->prepare("SELECT RoleName FROM roles WHERE RoleId = ?");
    $r->execute([$roleId]);
    $roleName = $r->fetchColumn();

    echo json_encode([
        'status'=>'success',
        'message'=>'Employee updated successfully',
        'data'=>[
            'Id'        => $id,
            'EmpCode'   => $empCode,
            'EmployeeName' => $name,
            'Username'  => $username,
            'RoleName'  => $roleName
        ]
    ]);
    exit;

} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>'DB error: '.$e->getMessage()]);
    exit;
}
?>

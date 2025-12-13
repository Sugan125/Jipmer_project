<?php
session_start();
include '../../config/db.php';

header("Content-Type: application/json");

// Must be role 4
if (!isset($_SESSION['role']) || $_SESSION['role'] != 4) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

// Get form values
$code = trim($_POST['EmployeeCode'] ?? '');
$name = trim($_POST['EmployeeName'] ?? '');
$username = trim($_POST['Username'] ?? '');
$password = trim($_POST['Password'] ?? '');
$roleId = intval($_POST['RoleId'] ?? 0);

// Validation
if ($code == "" || $name == "" || $username == "" || $password == "" || $roleId == 0) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

// Check duplicate username
$check = $conn->prepare("SELECT COUNT(*) FROM employee_master WHERE Username = ?");
$check->execute([$username]);

if ($check->fetchColumn() > 0) {
    echo json_encode(["status" => "error", "message" => "Username already exists"]);
    exit;
}

// Insert employee
$stmt = $conn->prepare("
    INSERT INTO employee_master (EmpCode, EmployeeName, Username, Password, RoleId)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$code, $name, $username, $password, $roleId]);

echo json_encode(["status" => "success", "message" => "Employee added successfully!"]);
exit;

<?php
session_start();
include '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 4) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$fy         = $_POST['financial_year'] ?? '';
$major      = trim($_POST['major'] ?? '');
$submajor   = trim($_POST['submajor'] ?? '');
$minor      = trim($_POST['minor'] ?? '');
$subminor   = trim($_POST['subminor'] ?? '');
$detail     = trim($_POST['detail'] ?? '');
$object     = trim($_POST['object'] ?? '');
$desc       = trim($_POST['description'] ?? '');

if ($fy == '' || $major == '') {
    echo json_encode(['status'=>'error','message'=>'Financial year and Major Head are required']);
    exit;
}

// Combine full HOA
$fullHOA = implode(' - ', array_filter([$major,$submajor,$minor,$subminor,$detail,$object]));

try {
    $stmt = $conn->prepare("INSERT INTO head_of_account_master 
        (FinancialYear, MajorHead, SubMajorHead, MinorHead, SubMinorHead, 
        DetailHead, ObjectHead, FullHOA, Description, CreatedBy)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $fy,$major,$submajor,$minor,$subminor,
        $detail,$object,$fullHOA,$desc,$_SESSION['user_id']
    ]);

    echo json_encode(['status'=>'success','message'=>'HOA added successfully']);
} 
catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=> $e->getMessage()]);
}

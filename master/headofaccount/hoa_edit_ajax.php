<?php
include '../../config/db.php';
include '../../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}


header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$id = intval($_POST['id']);

$fy         = $_POST['financial_year'] ?? '';
$major      = $_POST['major'] ?? '';
$submajor   = $_POST['submajor'] ?? '';
$minor      = $_POST['minor'] ?? '';
$subminor   = $_POST['subminor'] ?? '';
$detail     = $_POST['detail'] ?? '';
$object     = $_POST['object'] ?? '';
$desc       = $_POST['description'] ?? '';

$full = implode(" - ", array_filter([$major, $submajor, $minor, $subminor, $detail, $object]));

try {
    $stmt = $conn->prepare("
        UPDATE head_of_account_master 
        SET FinancialYear = ?, MajorHead = ?, SubMajorHead = ?, 
            MinorHead = ?, SubMinorHead = ?, DetailHead = ?, ObjectHead = ?, 
            FullHOA = ?, Description = ?
        WHERE Id = ?
    ");

    $stmt->execute([$fy, $major, $submajor, $minor, $subminor, $detail, $object, $full, $desc, $id]);

    echo json_encode(['status'=>'success','message'=>'HOA updated']);
} catch(Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

header('Content-Type: application/json');

$billtype = trim($_POST['billtype'] ?? '');

if ($billtype === '') {
    echo json_encode(['status' => 'error', 'message' => 'Bill Type is required']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO bill_type_master (BillType, IsActive, CreatedBy, CreatedDate) VALUES (?, 1, ?, GETDATE())");
    $stmt->execute([$billtype, $_SESSION['user_id']]);
    echo json_encode(['status' => 'success', 'message' => 'Bill Type added successfully']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

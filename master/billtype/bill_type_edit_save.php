<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
$billtype = trim($_POST['billtype'] ?? '');

if (!$id || $billtype === '') {
    echo json_encode(['status'=>'error','message'=>'Bill Type is required']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE bill_type_master 
                            SET BillType = ?, UpdatedBy = ?, UpdatedDate = GETDATE()
                            WHERE Id = ?");
    $stmt->execute([
        $billtype,
        $_SESSION['user_id'],
        $id
    ]);

    echo json_encode(['status'=>'success','message'=>'Bill Type updated successfully']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

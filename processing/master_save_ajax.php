<?php
include '../config/db.php';
include '../includes/auth.php';

header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$userId = $_SESSION['user_id'] ?? 0; // logged-in user

if (!$type || !$name) {
    echo json_encode(['status'=>'error','message'=>'Invalid input']);
    exit;
}
$fyId = intval($_POST['financial_year_id'] ?? 0);
try {
    if($type == 'finyear'){
        if($id){ // Edit
            $stmt = $conn->prepare("
                UPDATE fin_year_master 
                SET FinYear = ?, UpdatedBy = ?, UpdatedDate = GETDATE() 
                WHERE Id = ?
            ");
            $stmt->execute([$name, $userId, $id]);
            $msg = 'Financial Year updated';
        } else { // Add
            $stmt = $conn->prepare("
                INSERT INTO fin_year_master (FinYear, Status, CreatedBy, CreatedDate) 
                VALUES (?, 1, ?, GETDATE())
            ");
            $stmt->execute([$name, $userId]);
            $msg = 'Financial Year added';
        }
    }
    elseif($type == 'hoa'){
         if(!$fyId) {
        echo json_encode(['status'=>'error','message'=>'Select Financial Year for HOA']);
        exit;
    }
        if($id){ // Edit
        $stmt = $conn->prepare("
            UPDATE hoa_master 
            SET FullHOA = ?, FinancialYear = ?, UpdatedBy = ?, UpdatedDate = GETDATE()
            WHERE Id = ?
        ");
        $stmt->execute([$name, $fyId, $userId, $id]);
    } else { // Add
        $stmt = $conn->prepare("
            INSERT INTO hoa_master (FullHOA, FinancialYear, Status, CreatedBy, CreatedDate) 
            VALUES (?, ?, 1, ?, GETDATE())
        ");
        $stmt->execute([$name, $fyId, $userId]);
    }
    }
    else{
        echo json_encode(['status'=>'error','message'=>'Unknown type']);
        exit;
    }

    echo json_encode(['status'=>'success','message'=>$msg]);
} catch (Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}

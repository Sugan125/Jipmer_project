<?php
header('Content-Type: application/json');

include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);

 // Ensure only Audit/Processing role can access

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $billId = intval($_POST['bill_id'] ?? 0);
    $hoaId = intval($_POST['hoa'] ?? 0);
    $fy = trim($_POST['financial_year'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $gst = floatval($_POST['gst'] ?? 0);
    $it = floatval($_POST['it'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);
    $status = $_POST['status'] ?? 'Pass';
    $reason = trim($_POST['reason'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    // Basic validation
    if (!$billId) {
        $response['message'] = 'Invalid Bill ID';
    } elseif ($status === 'Returned' && $reason === '') {
        $response['message'] = 'Return reason required';
    } elseif ($remarks === '') {
        $response['message'] = 'Remarks required';
    } elseif (!$hoaId || $fy === '' || $amount <= 0 || $total <= 0) {
        $response['message'] = 'Please fill all required fields with valid values';
    } else {
        try {
            // Insert into bill_process
            $stmt = $conn->prepare("INSERT INTO bill_process 
                (BillId, HOAId, FinancialYear, Amount, GST, IT, TotalAmount, Status, ReasonForReturn, ReturnedDate, Remarks, ProcessedBy, ProcessedIP)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $returnedDate = ($status === 'Returned') ? date('Y-m-d') : null;

            $stmt->execute([
                $billId,
                $hoaId,
                $fy,
                $amount,
                $gst,
                $it,
                $total,
                $status,
                $reason ?: null,
                $returnedDate,
                $remarks,
                $_SESSION['user_id'],
                $_SERVER['REMOTE_ADDR']
            ]);

            // Update bill_entry status
            $u = $conn->prepare("UPDATE bill_entry SET Status = ? WHERE Id = ?");
            $u->execute([$status, $billId]);

            $response['status'] = 'success';
            $response['message'] = 'Bill processed successfully';
        } catch (Exception $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);

<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(1); // receiving/admin as needed

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $billType = trim($_POST['BillType'] ?? '');

    if ($billType === '') {
        echo json_encode(['status' => 'error', 'message' => 'Bill Type cannot be empty']);
        exit;
    }

    try {
        // Check if BillType already exists (case-insensitive)
        $stmt = $conn->prepare("SELECT Id FROM bill_type_master WHERE LOWER(BillType) = LOWER(?)");
        $stmt->execute([$billType]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo json_encode(['status' => 'success', 'message' => 'Bill Type already exists', 'id' => $existing['Id']]);
            exit;
        }

        // Insert new Bill Type
        $stmt = $conn->prepare("INSERT INTO bill_type_master (BillType, CreatedBy, CreatedDate, Status, IsActive) VALUES (?, ?, NOW(), 1, 1)");
        $stmt->execute([$billType, $_SESSION['user_id']]);
        $newId = $conn->lastInsertId();

        echo json_encode(['status' => 'success', 'message' => 'Bill Type added successfully', 'id' => $newId]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}

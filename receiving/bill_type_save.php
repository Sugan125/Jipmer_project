<?php
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
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}
 // receiving/admin as needed

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

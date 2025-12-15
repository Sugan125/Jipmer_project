<?php
include '../../config/db.php';
include '../../includes/auth.php';

$page = 'hoa_list.php'; // parent page for permission

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl = ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], $page]);

if ($stmt->fetchColumn() == 0) {
    echo json_encode(["data" => []]);
    exit;
}

$data = $conn->query("
    SELECT 
        h.Id,
        h.FinancialYear,
        h.FullHOA,
        h.Description,
        u.EmployeeName AS CreatedByName,
        DATE_FORMAT(h.CreatedDate,'%d-%m-%Y') AS CreatedDate
    FROM head_of_account_master h
    LEFT JOIN employee_master u ON h.CreatedBy = u.Id
    ORDER BY h.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as &$row) {
    $row['Actions'] = '
        <button class="btn btn-sm btn-primary edit-btn" data-id="'.$row['Id'].'">
            Edit
        </button>
        <button class="btn btn-sm btn-danger delete-btn" data-id="'.$row['Id'].'">
            Delete
        </button>';
}

echo json_encode(["data" => $data]);
exit;

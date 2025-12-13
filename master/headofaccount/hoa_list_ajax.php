<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

header('Content-Type: application/json'); // IMPORTANT

$hoas = $conn->query("
    SELECT h.*, u.EmployeeName AS CreatedByName
    FROM head_of_account_master h
    LEFT JOIN employee_master u ON h.CreatedBy = u.Id
    ORDER BY h.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$data = [];

foreach ($hoas as $h) {
    $actions = '<button class="btn btn-sm btn-outline-primary edit-btn" data-id="'.$h['Id'].'">Edit</button> ';
    $actions .= '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$h['Id'].'">Delete</button>';

    $data[] = [
        'Id' => $h['Id'],
        'FinancialYear' => $h['FinancialYear'],
        'FullHOA' => $h['FullHOA'],
        'Description' => $h['Description'],
        'CreatedByName' => $h['CreatedByName'],
        'CreatedDate' => $h['CreatedDate'],
        'Actions' => $actions
    ];
}

echo json_encode(['data' => $data]);

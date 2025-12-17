<?php
include '../config/db.php';
session_start();

$bill_type = $conn->query("
    SELECT Id, BillType 
    FROM bill_type_master 
    WHERE Status=1 AND IsActive=1 
    ORDER BY BillType
")->fetchAll(PDO::FETCH_ASSOC);

echo '<option value="">Select</option>';

foreach ($bill_type as $b) {
    echo "<option value='{$b['Id']}'>".htmlspecialchars($b['BillType'])."</option>";
}



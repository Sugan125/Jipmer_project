<?php
include '../config/db.php';
$data = $conn->query("SELECT Id, DebitName FROM account_debit_master WHERE Status=1")->fetchAll();
echo '<option value="">Select</option>';
foreach($data as $r){
    echo "<option value='{$r['Id']}'>".htmlspecialchars($r['DebitName'])."</option>";
}

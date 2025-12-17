<?php
include '../config/db.php';
$data = $conn->query("SELECT Id, CreditName FROM account_credit_master WHERE Status=1")->fetchAll();
echo '<option value="">Select</option>';
foreach($data as $r){
    echo "<option value='{$r['Id']}'>".htmlspecialchars($r['CreditName'])."</option>";
}

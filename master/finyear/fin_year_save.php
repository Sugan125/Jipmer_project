<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);


if(isset($_POST['iscurrent'])){
$conn->query("UPDATE fin_year_master SET IsCurrent=0");
}


$stmt = $conn->prepare("INSERT INTO fin_year_master (FinYear, IsCurrent, CreatedBy) VALUES (?,?,?)");
$stmt->execute([
$_POST['finyear'],
isset($_POST['iscurrent']) ? 1 : 0,
$_SESSION['user_id']
]);
header('Location: fin_year_master.php');
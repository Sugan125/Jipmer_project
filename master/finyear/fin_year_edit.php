<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);


$id = $_GET['id'];


if($_SERVER['REQUEST_METHOD']==='POST'){
if(isset($_POST['iscurrent'])){
$conn->query("UPDATE fin_year_master SET IsCurrent=0");
}
$stmt = $conn->prepare("UPDATE fin_year_master SET FinYear=?, IsCurrent=?, UpdatedBy=?, UpdatedDate=GETDATE() WHERE Id=?");
$stmt->execute([
$_POST['finyear'],
isset($_POST['iscurrent']) ? 1 : 0,
$_SESSION['user_id'],
$id
]);
header('Location: fin_year_master.php');
exit;
}


$fy = $conn->prepare("SELECT * FROM fin_year_master WHERE Id=?");
$fy->execute([$id]);
$data = $fy->fetch();
?>
<form method="post">
<input type="text" name="finyear" value="<?= htmlspecialchars($data['FinYear']) ?>" required>
<label><input type="checkbox" name="iscurrent" <?= $data['IsCurrent']?'checked':'' ?>> Current</label>
<button>Update</button>
</form>
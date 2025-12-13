<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$id = intval($_GET['id'] ?? 0);
$type = $conn->prepare("SELECT * FROM account_debit_master WHERE Id=?");
$type->execute([$id]);
$data = $type->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt = $conn->prepare("UPDATE account_debit_master SET DebitName=?, UpdatedBy=?, UpdatedDate=GETDATE() WHERE Id=?");
    $stmt->execute([$_POST['DebitName'], $_SESSION['user_id'], $id]);
    header('Location: debit_master.php');
    exit;
}
?>
<form method="post">
    <input type="text" name="DebitName" value="<?= htmlspecialchars($data['DebitName']) ?>" required>
    <button>Update</button>
</form>

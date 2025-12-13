<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$id = intval($_GET['id'] ?? 0);
$type = $conn->prepare("SELECT * FROM account_credit_master WHERE Id=?");
$type->execute([$id]);
$data = $type->fetch();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $stmt = $conn->prepare("UPDATE account_credit_master SET CreditName=?, UpdatedBy=?, UpdatedDate=GETDATE() WHERE Id=?");
    $stmt->execute([$_POST['CreditName'], $_SESSION['user_id'], $id]);
    header('Location: credit_master.php');
    exit;
}
?>
<form method="post">
    <input type="text" name="CreditName" value="<?= htmlspecialchars($data['CreditName']) ?>" required>
    <button>Update</button>
</form>

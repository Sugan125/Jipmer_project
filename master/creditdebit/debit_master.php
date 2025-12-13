<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$debits = $conn->query("SELECT * FROM account_debit_master ORDER BY DebitName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Debit Master</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/dataTables.bootstrap5.min.css">
</head>
<body>
<div class="container mt-4">
    <h4>Account Debit To Master</h4>

    <!-- Add Form -->
    <form method="post" action="debit_save.php" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="DebitName" class="form-control" placeholder="Debit Name" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary">Add</button>
        </div>
    </form>

    <!-- List -->
    <table id="debitTable" class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Debit Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($debits as $i=>$c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($c['DebitName']) ?></td>
                <td><?= $c['Status'] ? 'Active':'Inactive' ?></td>
                <td>
                    <a href="debit_edit.php?id=<?= $c['Id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="debit_delete.php?id=<?= $c['Id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/jquery.dataTables.min.js"></script>
<script src="../js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    $('#debitTable').DataTable({
        "pageLength": 10
    });
});
</script>
</body>
</html>

<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4); // only admin

$credits = $conn->query("SELECT * FROM account_credit_master ORDER BY CreditName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Credit Master</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/dataTables.bootstrap5.min.css">
</head>
<body>
<div class="container mt-4">
    <h4>Account Credit To Master</h4>

    <!-- Add Form -->
    <form method="post" action="credit_save.php" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="CreditName" class="form-control" placeholder="Credit Name" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary">Add</button>
        </div>
    </form>

    <!-- List -->
    <table id="creditTable" class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Credit Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($credits as $i=>$c): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($c['CreditName']) ?></td>
                <td><?= $c['Status'] ? 'Active':'Inactive' ?></td>
                <td>
                    <a href="credit_edit.php?id=<?= $c['Id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="credit_delete.php?id=<?= $c['Id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
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
    $('#creditTable').DataTable({
        "pageLength": 10
    });
});
</script>
</body>
</html>


<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);


$years = $conn->query("SELECT * FROM fin_year_master ORDER BY FinYear DESC")
->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Financial Year Master</title>
<link rel="stylesheet" href="../../css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
<h4>Financial Year Master</h4>


<form method="post" action="fin_year_save.php" class="row g-2">
<div class="col-md-4">
<input type="text" name="finyear" class="form-control" placeholder="2024-25" required>
</div>
<div class="col-md-3">
<label><input type="checkbox" name="iscurrent"> Current Year</label>
</div>
<div class="col-md-2">
<button class="btn btn-primary">Add</button>
</div>
</form>


<table class="table table-bordered mt-3">
<thead>
<tr>
<th>#</th>
<th>Financial Year</th>
<th>Current</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($years as $i=>$y): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($y['FinYear']) ?></td>
<td><?= $y['IsCurrent'] ? 'Yes' : 'No' ?></td>
<td>
<a href="fin_year_edit.php?id=<?= $y['Id'] ?>" class="btn btn-sm btn-warning">Edit</a>
<a href="fin_year_delete.php?id=<?= $y['Id'] ?>" onclick="return confirm('Delete?')" class="btn btn-sm btn-danger">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>
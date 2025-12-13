<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

$types = $conn->query("SELECT * FROM bill_type_master ORDER BY BillType")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Bill Type Master</title>
  <link rel="stylesheet" href="../../css/bootstrap.min.css">
  <link rel="stylesheet" href="../../js/datatables/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="../../css/all.min.css">
  <script src="../../js/jquery-3.7.1.min.js"></script>
  <script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
  <script src="../../js/datatables/jquery.dataTables.min.js"></script>
  <script src="../../js/datatables/dataTables.bootstrap5.min.js"></script>
  <script src="../../js/sweetalert2.all.min.js"></script>
</head>
<body>
<div class="container mt-4">
  <h4>Bill Type Master</h4>

  <form id="addForm" class="row g-2 mb-3">
    <div class="col-md-6">
       <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addBillTypeModal">
    ➕ Add Bill Type
</button>
    </div>
  

  </form>

  <table id="billTable" class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>#</th>
        <th>Bill Type</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($types as $i=>$t): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($t['BillType']) ?></td>
          <td>
            <?php if($t['IsActive']): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <button class="btn btn-sm btn-warning edit-btn" data-id="<?= $t['Id'] ?>" data-name="<?= htmlspecialchars($t['BillType']) ?>">Edit</button>
            <button class="btn btn-sm btn-info toggle-btn" data-id="<?= $t['Id'] ?>" data-status="<?= $t['IsActive'] ?>">
              <?= $t['IsActive'] ? 'Deactivate' : 'Activate' ?>
            </button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $t['Id'] ?>">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Bill Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit_id">
        <input type="text" name="billtype" id="edit_name" class="form-control" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<div class="modal fade" id="addBillTypeModal" tabindex="-1" aria-labelledby="addBillTypeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addBillTypeForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addBillTypeLabel">Add Bill Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="billtype" class="form-control" placeholder="Enter Bill Type" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function(){
  $('#billTable').DataTable();

  // Add Bill Type
  $('#addForm').submit(function(e){
    e.preventDefault();
    $.post('bill_type_add.php', $(this).serialize(), function(resp){
      if(resp.status==='success') location.reload();
      else alert(resp.message);
    }, 'json');
  });

  // Edit Bill Type
  $('.edit-btn').click(function(){
    $('#edit_id').val($(this).data('id'));
    $('#edit_name').val($(this).data('name'));
    new bootstrap.Modal(document.getElementById('editModal')).show();
  });

  $('#editForm').submit(function(e){
    e.preventDefault();
    $.post('bill_type_edit_save.php', $(this).serialize(), function(resp){
      if(resp.status==='success') location.reload();
      else alert(resp.message);
    }, 'json');
  });

  // Toggle Active/Inactive
  $('.toggle-btn').click(function(){
    var id = $(this).data('id');
    $.post('bill_type_toggle.php', {id:id}, function(resp){
      if(resp.status==='success') location.reload();
      else alert(resp.message);
    }, 'json');
  });

  // Delete
  $('.delete-btn').click(function(){
    var id = $(this).data('id');
    if(confirm('Are you sure to delete?')){
      $.post('bill_type_delete.php', {id:id}, function(resp){
        if(resp.status==='success') location.reload();
        else alert(resp.message);
      }, 'json');
    }
  });

  $('#addBillTypeForm').submit(function(e){
    e.preventDefault();
    $.ajax({
        url: 'bill_type_add.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(resp){
            if(resp.status === 'success'){
                Swal.fire('Success', resp.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', resp.message, 'error');
            }
        },
        error: function(){
            Swal.fire('Error', 'Something went wrong!', 'error');
        }
    });
});
});
</script>
</body>
</html>

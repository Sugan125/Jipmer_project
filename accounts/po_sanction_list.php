<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
include '../config/db.php';
include '../includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>PO & Sanction Orders List</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:50px 30px;}
.card{max-width:1400px;margin:auto;}
.table td, .table th{vertical-align:middle; white-space:nowrap;}
.small-muted{font-size:12px;color:#6c757d;}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
<div class="card p-4 shadow">
  <h4 class="text-primary mb-4"><i class="fa fa-list"></i> PO & Sanction Orders List</h4>

  <div class="text-end mb-3">
      <a href="po_sanction_entry.php" class="btn btn-success"><i class="fa fa-plus"></i> Add New PO</a>
  </div>

  <div class="table-responsive">
  <table class="table table-bordered table-hover">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>PO Number</th>
      <th>PO Date</th>
      <th>GST No</th>

      <th class="text-end">PO Base</th>
      <th class="text-end">PO GST</th>
      <th class="text-end">PO IT</th>
      <th class="text-end">PO Net</th>

      <th class="text-center">Items</th>

      <th class="text-end">Sanction Total</th>
      <th class="text-center">Sanctions</th>

      <th class="text-end">Balance (Net)</th>
      <th class="text-end">Created</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
<?php
$sql = "
SELECT 
    p.Id,
    p.POOrderNo,
    p.POOrderDate,
    p.GSTNumber,
    p.POAmount,
    p.PONetAmount,
    p.CreatedDate,

    ISNULL(pi.ItemCount,0) AS ItemCount,
    ISNULL(pi.SumGST,0)    AS SumGST,
    ISNULL(pi.SumIT,0)     AS SumIT,

    ISNULL(s.SumSanction,0) AS SumSanction,
    ISNULL(s.SanCount,0)    AS SanCount
FROM po_master p
LEFT JOIN (
    SELECT 
        POId,
        COUNT(*) AS ItemCount,
        SUM(ISNULL(GSTAmount,0)) AS SumGST,
        SUM(ISNULL(ITAmount,0))  AS SumIT
    FROM po_items
    GROUP BY POId
) pi ON pi.POId = p.Id
LEFT JOIN (
    SELECT 
        POId,
        COUNT(*) AS SanCount,
        SUM(ISNULL(SanctionAmount,0)) AS SumSanction
    FROM sanction_order_master
    GROUP BY POId
) s ON s.POId = p.Id
ORDER BY p.Id DESC
";
$stmt = $conn->query($sql);
$pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($pos)): 
$i = 1;
foreach($pos as $po):
  $balanceNet = ((float)$po['PONetAmount']) - ((float)$po['SumSanction']);
?>
    <tr>
      <td><?= $i++ ?></td>
      <td class="fw-bold"><?= htmlspecialchars($po['POOrderNo']) ?></td>
      <td><?= htmlspecialchars($po['POOrderDate']) ?></td>
      <td><?= htmlspecialchars($po['GSTNumber'] ?? '-') ?></td>

      <td class="text-end"><?= number_format((float)$po['POAmount'],2) ?></td>
      <td class="text-end"><?= number_format((float)$po['SumGST'],2) ?></td>
      <td class="text-end"><?= number_format((float)$po['SumIT'],2) ?></td>
      <td class="text-end text-success fw-bold"><?= number_format((float)$po['PONetAmount'],2) ?></td>

      <td class="text-center">
        <span class="badge bg-secondary"><?= (int)$po['ItemCount'] ?></span>
      </td>

      <td class="text-end"><?= number_format((float)$po['SumSanction'],2) ?></td>
      <td class="text-center">
        <span class="badge bg-info"><?= (int)$po['SanCount'] ?></span>
      </td>

      <td class="text-end fw-bold <?= ($balanceNet < 0 ? 'text-danger' : 'text-primary') ?>">
        <?= number_format($balanceNet,2) ?>
      </td>

      <td class="text-end">
        <div class="small-muted"><?= htmlspecialchars($po['CreatedDate'] ?? '-') ?></div>
      </td>

      <td>
        <!-- ✅ Edit/View via POST (AJAX sets session + redirect) -->
        <button type="button" class="btn btn-sm btn-primary openEdit" data-id="<?= (int)$po['Id'] ?>">
          <i class="fa fa-edit"></i> Edit
        </button>

        <button type="button" class="btn btn-sm btn-info openView" data-id="<?= (int)$po['Id'] ?>">
          <i class="fa fa-eye"></i> View
        </button>

        <button type="button" class="btn btn-sm btn-danger deletePO" data-id="<?= (int)$po['Id'] ?>">
          <i class="fa fa-trash"></i> Delete
        </button>
      </td>
    </tr>
<?php endforeach; ?>
  <?php else: ?>
    <!-- ✅ No records row -->
    <tr>
      <td colspan="14" class="text-center text-muted fw-bold py-4">
        <i class="fa fa-folder-open me-2"></i> No records found
      </td>
    </tr>
<?php endif; ?>
</tbody>
  </table>
  </div>
</div>
</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
function openByPost(poId, mode){
  $.ajax({
    url: 'po_context_set.php',
    type: 'POST',
    dataType: 'json',
    data: { po_id: poId, mode: mode },
    success: function(res){
      if(res.status === 'success'){
        if(mode === 'edit') window.location.href = 'po_sanction_entry_edit.php';
        if(mode === 'view') window.location.href = 'po_sanction_details.php';
      }else{
        Swal.fire('Error', res.message || 'Unable to open', 'error');
      }
    },
    error: function(){
      Swal.fire('Error', 'AJAX Error', 'error');
    }
  });
}

$(document).on('click', '.openEdit', function(){
  openByPost($(this).data('id'), 'edit');
});

$(document).on('click', '.openView', function(){
  openByPost($(this).data('id'), 'view');
});

$(document).on('click','.deletePO', function(){
  var poId = $(this).data('id');
  Swal.fire({
    icon:'warning',
    title:'Are you sure?',
    text:'This will delete the PO and all its items/sanctions!',
    showCancelButton:true,
    confirmButtonColor:'#d33',
    cancelButtonColor:'#3085d6',
    confirmButtonText:'Yes, delete it!'
  }).then((result)=>{
    if(!result.isConfirmed) return;

    $.ajax({
      url:'po_sanction_delete.php',
      type:'POST',
      data:{po_id:poId},
      dataType:'json',
      success:function(res){
        if(res.status==='success'){
          Swal.fire('Deleted!','PO deleted successfully.','success').then(()=>location.reload());
        }else{
          Swal.fire('Error',res.message,'error');
        }
      },
      error:function(){
        Swal.fire('Error','AJAX Error','error');
      }
    });
  });
});
</script>

</body>
</html>

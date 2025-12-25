<?php
include '../config/db.php';
include '../includes/auth.php';

/* ===== Permission Check ===== */
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
$bill_type = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1 and IsActive =1 ORDER BY BillType")->fetchAll(PDO::FETCH_ASSOC);

/* ===== Dropdown Data ===== */
$billTypes = $conn->query("SELECT Id, BillType FROM bill_type_master WHERE Status=1")->fetchAll(PDO::FETCH_ASSOC);
$invoices = $conn->query("
    SELECT i.*
FROM invoice_master i
LEFT JOIN bill_invoice_map bim ON i.Id = bim.InvoiceId
WHERE bim.InvoiceId IS NULL
ORDER BY i.InvoiceNo
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Details</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<link href="../js/select2/select2.min.css" rel="stylesheet">
<script src="../js/select2/select2.min.js"></script>
<style>
.page-content{
    margin-left:240px;
    padding:90px 30px 30px;
}
.card{
    max-width:900px;
    margin:auto;
}
.multipleselect {
    min-height: 150px!important;
}
.invoice-box {
    max-height: 260px;
    overflow-y: auto;
    background: #fff;
}

.invoice-box::-webkit-scrollbar {
    width: 6px;
}
.invoice-box::-webkit-scrollbar-thumb {
    background: #bbb;
    border-radius: 5px;
}

</style>
</head>

<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<div class="card shadow-sm p-4">
<h4 class="mb-4 text-primary">
<i class="fa fa-file-invoice me-2"></i> Bill Details Entry
</h4>

<form id="billDetailsForm">

<div class="row g-3">

<!-- Bill Number -->
<div class="col-md-6">
<label class="form-label">Bill Number</label>
<input type="text" name="BillNumber" class="form-control" required>
</div>

<!-- Bill Received Date -->
<div class="col-md-6">
<label class="form-label">Bill Received Date</label>
<input type="date" name="BillReceivedDate" class="form-control" required>
</div>

<!-- Received From Section -->
<div class="col-md-6">
<label class="form-label">Received From Section</label>
<input type="text" name="ReceivedFromSection" class="form-control" required>
</div>

<!-- Section DA -->
<div class="col-md-6">
<label class="form-label">Section DA Name</label>
<input type="text" name="SectionDAName" class="form-control">
</div>

<!-- Bill Type -->
<div class="col-md-6">
        <label class="form-label">Bill Type</label>

    <div class="input-group">
        <select id="BillTypeId"  name="BillTypeId" class="form-select">
            <option value="">Select</option>
            <?php foreach($bill_type as $b): ?>
                <option value="<?= $b['Id'] ?>">
                    <?= htmlspecialchars($b['BillType']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($_SESSION['role'] == 5): ?>
           <button type="button" class="btn btn-light border" id="addBillTypeBtn" title="Add">
    <i class="fa fa-plus text-success"></i>
</button>

<button type="button" class="btn btn-light border" id="editBillTypeBtn" title="Edit">
    <i class="fa fa-pen text-primary"></i>
</button>

<button type="button" class="btn btn-light border" id="deleteBillTypeBtn" title="Delete">
    <i class="fa fa-times text-danger"></i>
</button>
            </button>
        <?php endif; ?>
    </div>
</div>


<!-- PFMS -->
<div class="col-md-6">
<label class="form-label">PFMS Unique Number</label>
<input type="text" name="PFMSUniqueNo" class="form-control">
</div>

<!-- PO Order -->
<div class="col-md-6">
<label class="form-label">PO Order Number</label>
<input type="text" name="POOrderNo" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">PO Order Date</label>
<input type="date" name="POOrderDate" class="form-control">
</div>

<!-- IT -->
<div class="col-md-4">
<label class="form-label">IT</label>
<input type="number" step="0.01" name="IT" class="form-control">
</div>

<!-- GST -->
<div class="col-md-4">
<label class="form-label">GST</label>
<input type="number" step="0.01" name="GST" class="form-control">
</div>

<!-- TDS -->
<div class="col-md-4">
<label class="form-label">TDS Type</label>
<select name="TDSType" class="form-select">
<option value="">None</option>
<option value="GST">GST</option>
<option value="IT">IT</option>
</select>
</div>
<div class="col-12">
    <label class="form-label fw-bold mb-2">Attach Invoices</label>

    <div class="invoice-box border rounded p-3">
        <?php foreach($invoices as $i): ?>
            <div class="form-check mb-2">
                <input class="form-check-input"
                       type="checkbox"
                       name="Invoices[]"
                       value="<?= $i['Id'] ?>"
                       id="inv<?= $i['Id'] ?>">

                <label class="form-check-label" for="inv<?= $i['Id'] ?>">
                    <strong><?= htmlspecialchars($i['InvoiceNo']) ?></strong>
                    <span class="text-muted"> | <?= htmlspecialchars($i['VendorName']) ?></span>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <small class="text-muted">
        ✔ Select one or more invoices 
    </small>
</div>





<div class="mt-4 text-end">
<button class="btn btn-success">
<i class="fa fa-save me-1"></i> Save Bill Details
</button>
</div>

</form>
</div>
</div>

<!-- Bill Type Modal -->
<div class="modal fade" id="billTypeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="billTypeForm">
        <div class="modal-header">
          <h5 class="modal-title">Add Bill Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="billTypeId">
          <div class="mb-3">

        
            <label class="form-label">Bill Type Name</label>
            <input type="text" name="BillType" id="BillTypeName" class="form-control" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
        </div>

        <!-- MASTER MODAL -->
<div class="modal fade" id="masterModal">
<div class="modal-dialog">
<div class="modal-content">
<form id="masterForm">
<div class="modal-header">
<h5 id="masterTitle"></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" id="masterId" name="id">
<input type="hidden" id="masterType" name="type">
<label id="masterLabel"></label>
<input type="text" id="masterName" name="name" class="form-control" required>
</div>
<div class="modal-footer">
<button class="btn btn-primary">Save</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
</form>
</div>
</div>
</div>
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function(){
    $('#invoiceSelect').select2({
        placeholder: "Select invoices",
        width: '100%',
        allowClear: true
    });
});
$('#selectAllInv').click(() => {
    $('.invoice-box input[type=checkbox]').prop('checked', true);
});
$('#clearAllInv').click(() => {
    $('.invoice-box input[type=checkbox]').prop('checked', false);
});
$("#billDetailsForm").on("submit", function(e){
    e.preventDefault();

    $.ajax({
        url: "bill_details_submit.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success:function(res){
            if(res.status === "success"){
                Swal.fire({
                    icon:'success',
                    title:'Saved',
                    text:'Bill details saved successfully',
                    timer:1500,
                    showConfirmButton:false
                });
                $("#billDetailsForm")[0].reset();
            }else{
                Swal.fire('Error', res.message, 'error');
            }
        },
        error:function(xhr){
            Swal.fire('Server Error','Check PHP error / DB connection','error');
        }
    });
});

function openModal(type, id='', text='') {
    $('#masterType').val(type);
    $('#masterId').val(id);
    $('#masterName').val(text);
    const label = type==='bill'?'Bill Type':type==='credit'?'Credit Name':'Debit Name';
    $('#masterLabel').text(label);
    $('#masterTitle').text((id?'Edit ':'Add ')+label);
    $('#masterModal').modal('show');
}

$('#addBillTypeBtn').click(()=>openModal('bill'));
$('#editBillTypeBtn').click(()=>{
    const id=$('#BillTypeId').val();
    if(!id) return Swal.fire('Select Bill Type');
    openModal('bill',id,$('#BillTypeId option:selected').text().trim());
});


/* SAVE MASTER */
$('#masterForm').submit(function(e){
e.preventDefault();
$.post('master_save_ajax.php',$(this).serialize(),function(){
    location.reload();
});
});


$('#deleteBillTypeBtn').click(function () {

    const id   = $('#BillTypeId').val();
    const text = $('#BillTypeId option:selected').text().trim();

    if (!id) {
        Swal.fire('Select Bill Type to delete');
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: `Delete "${text}" ?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (result.isConfirmed) {
            $.post('master_delete_ajax.php', {
                id: id,
                type: 'bill'
            }, function () {

                Swal.fire('Deleted!', 'Bill Type removed', 'success');
                reloadBillTypes();

            }).fail(() => {
                Swal.fire('Error', 'Unable to delete', 'error');
            });
        }
    });
});

function reloadBillTypes() {
    $.get('bill_type_fetch_ajax.php', function (html) {
        $('#BillTypeId').html(html);
    });
}
</script>

</body>
</html>

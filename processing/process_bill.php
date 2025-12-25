<?php
include '../config/db.php';
include '../includes/auth.php';
$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);

$billId = intval($_POST['bill_id'] ?? 0);
?>
<?php if (!$billId): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Bill Not Found',
        text: 'The selected bill does not exist or was already processed.',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = 'process_list.php';
    });
});
</script>
<?php endif; ?>
<?php

// Fetch bill details
$stmt = $conn->prepare("SELECT be.*,bi.* FROM bill_entry be left join bill_initial_entry bi on bi.Id=be.BillInitialId WHERE be.BillInitialId = ?");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<?php if (!$bill): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'error',
        title: 'Bill Not Found',
        text: 'The selected bill does not exist or was already processed.',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = 'process_list.php';
    });
});
</script>
<?php endif; ?>
<?php
$finYears = $conn->query("SELECT Id, FinYear FROM fin_year_master WHERE Status=1 ORDER BY FinYear DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Process Bill</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
body { margin: 0; min-height: 100vh; background-color: #f8f9fa; }
.topbar-fixed { position: fixed; top: 0; width: 100%; z-index: 1030; }
.sidebar-fixed { position: fixed; top: 70px; bottom: 0; width: 240px; overflow-y: auto; background-color: #343a40; }
.page-content { margin-left: 240px; padding: 150px 20px 20px 20px; }
</style>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>
<div class="page-content">
    <div class="card shadow rounded">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-file-invoice"></i> Process Bill #<?= htmlspecialchars($bill['BillNumber']) ?></h4>
        </div>
        <div class="card-body">
            <form id="processBillForm">
                <input type="hidden" name="bill_id" value="<?= $billId ?>">

                      <!-- Bill Info -->
                <div class="mb-3"><strong>Bill No:</strong> <?= htmlspecialchars($bill['BillNumber']) ?></div>
                <div class="mb-3"><strong>Received:</strong> <?= date('d/m/Y', strtotime($bill['BillReceivedDate'])) ?></div>

                <!-- Financial Year -->
                <div class="mb-3">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year" id="financial_year" class="form-select" required>
                        <?php 
                        $currentYear = date('Y');
                        $currentMonth = date('n');
                        $currentFY = ($currentMonth >= 4) ? $currentYear.'-'.($currentYear+1) : ($currentYear-1).'-'.$currentYear;
                        foreach ($finYears as $fy): ?>
                        <option value="<?= $fy['Id'] ?>" <?= ($fy['FinYear']==$currentFY)?'selected':'' ?>>
                            <?= $fy['FinYear'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if($_SESSION['role']==5): ?>
                    <button type="button" class="btn btn-light border" id="addFinYearBtn"><i class="fa fa-plus text-success"></i></button>
                    <button type="button" class="btn btn-light border" id="editFinYearBtn"><i class="fa fa-pen text-primary"></i></button>
                    <button type="button" class="btn btn-light border" id="deleteFinYearBtn"><i class="fa fa-times text-danger"></i></button>
                    <?php endif; ?>
                </div>

                <!-- HOA -->
                <div class="mb-3">
                    <label class="form-label">HOA</label>
                    <select name="hoa" id="hoa" class="form-select" required>
                        <option value="">Select HOA</option>
                    </select>
                    <!--  //if($_SESSION['role']==5): ?>
                    <button type="button" class="btn btn-light border" id="addHoaBtn"><i class="fa fa-plus text-success"></i></button>
                    <button type="button" class="btn btn-light border" id="editHoaBtn"><i class="fa fa-pen text-primary"></i></button>
                    <button type="button" class="btn btn-light border" id="deleteHoaBtn"><i class="fa fa-times text-danger"></i></button>
                     //endif; ?> -->
                </div>

                <!-- Amounts -->
                  <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Amount</label>
                        <input name="amount" id="amount" class="form-control" type="number" step="0.01" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>GST</label>
                        <input name="gst" id="gst" class="form-control" type="number" step="0.01" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>IT</label>
                        <input name="it" id="it" class="form-control" type="number" step="0.01" required>
                    </div>
                </div>

                <!-- Total -->
                <div class="mb-3">
                    <label>Total Amount</label>
                    <input name="total" id="total" class="form-control" type="number" step="0.01" required>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="Pass">Pass</option>
                        <option value="Returned">Return</option>
                        <option value="Cancelled">Cancel</option>
                    </select>
                </div>

                <!-- Return Reason -->
                <div id="returnReasonDiv" style="display:none;" class="mb-3">
                    <label>Reason for Return</label>
                    <textarea name="reason" class="form-control"></textarea>
                </div>

                <!-- Remarks -->                
                <div class="mb-3">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" required></textarea>
                </div>

                  <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Processing
                </button>
            </form>
        </div>
    </div>
</div>

<!-- MASTER MODAL -->
<div class="modal fade" id="masterModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="masterForm">
        <div class="modal-header">
          <h5 class="modal-title" id="masterTitle">Add</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="masterId" name="id">
          <input type="hidden" id="masterType" name="type">
          <div class="mb-3">
            <label class="form-label" id="masterLabel">Name</label>
            <input type="text" id="masterName" name="name" class="form-control" required>
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
<!-- JS Libraries -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
 function loadHOA(fy, selectedHOA = '') {
    if(fy === '') {
        $('#hoa').html('<option value="">Select HOA</option>');
        return;
    }
    $.ajax({
        url: 'get_hoa_by_fy.php',
        type: 'GET',
        data: { fy: fy },
        dataType: 'json',
        success: function(data){
            let options = '<option value="">Select HOA</option>';
            $.each(data, function(i, hoa){
                options += `<option value="${hoa.Id}" data-fy="${hoa.FinYearId}">${hoa.FullHOA}</option>`;
            });
            $('#hoa').html(options);
        },
        error: function(){
            Swal.fire('Error', 'Could not load HOA options', 'error');
        }
    });
}

console.log($('#financial_year').val());
// Load HOA on page load
loadHOA($('#financial_year').val());

$('#financial_year').on('change', function(){
    loadHOA($(this).val());
});


    // Show/Hide Return Reason
    $('#status').on('change', function(){
        if(this.value === 'Returned') {
            $('#returnReasonDiv').show();
        } else {
            $('#returnReasonDiv').hide();
        }
    });

    // Auto-calculate Total = Amount + GST - IT
    $('#amount, #gst, #it').on('input', function(){
        let amount = parseFloat($('#amount').val()) || 0;
        let gst = parseFloat($('#gst').val()) || 0;
        let it = parseFloat($('#it').val()) || 0;
        $('#total').val((amount + gst - it).toFixed(2));
    });

    // AJAX Form Submit
    $('#processBillForm').on('submit', function(e){
        e.preventDefault();

        $.ajax({
            url: 'bill_process_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Processed!',
                        text: resp.message,
                        confirmButtonText: 'OK'
                    }).then(() => { window.location.href = 'process_list.php'; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
                }
            },
            error: function(){
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
            }
        });
    });

    const masterModal = new bootstrap.Modal(document.getElementById('masterModal'), {});

    function openModal(type, id='', text='', fyId='') {
    $('#masterType').val(type);
    $('#masterId').val(id);
    $('#masterName').val(text);

    let label = 'Name';
    if(type === 'finyear') label = 'Financial Year';
    if(type === 'hoa') label = 'HOA';

    $('#masterLabel').text(label);
    $('#masterTitle').text((id ? 'Edit ' : 'Add ') + label);

    // Show Financial Year only for HOA
    if(type === 'hoa') {
        $('#modalFinYearDiv').show();
        $('#modalFinYear').val(fyId || '');
    } else {
        $('#modalFinYearDiv').hide();
        $('#modalFinYear').val('');
    }

    $('#masterModal').modal('show');
}


    /* Financial Year */
    $('#addFinYearBtn').click(()=>openModal('finyear'));
    $('#editFinYearBtn').click(()=> {
        const id = $('#financial_year').val();
        if(!id) return Swal.fire('Select Financial Year');
        openModal('finyear', id, $('#financial_year option:selected').text().trim());
    });

    $('#deleteFinYearBtn').click(()=>{
        const id = $('#financial_year').val();
        const text = $('#financial_year option:selected').text().trim();
        if(!id) return Swal.fire('Select Financial Year');
        Swal.fire({
            title: 'Delete "'+text+'"?',
            icon: 'warning',
            showCancelButton:true,
            confirmButtonText:'Yes'
        }).then(r=>{if(r.isConfirmed) $.post('master_delete_ajax.php',{id,type:'finyear'},()=>location.reload());});
    });

    /* HOA */
    function loadHOA(fy, selectedHOA=''){
        if(fy===''){ $('#hoa').html('<option value="">Select HOA</option>'); return; }
        $.getJSON('get_hoa_by_fy.php',{fy:fy},data=>{
            let opts = '<option value="">Select HOA</option>';
            $.each(data,(i,h)=>{opts+=`<option value="${h.Id}" ${(h.Id==selectedHOA?'selected':'')}>${h.FullHOA}</option>`;});
            $('#hoa').html(opts);
        });
    }

    $('#addHoaBtn').click(()=>openModal('hoa'));
   $('#editHoaBtn').click(()=> {
    const id = $('#hoa').val();
    if(!id) return Swal.fire('Select HOA');
    const text = $('#hoa option:selected').text().trim();
    const fyId = $('#hoa option:selected').data('fy'); // Store FY in option data attribute
    openModal('hoa', id, text, fyId);
});
    $('#deleteHoaBtn').click(()=>{
        const id = $('#hoa').val();
        const text = $('#hoa option:selected').text().trim();
        if(!id) return Swal.fire('Select HOA');
        Swal.fire({
            title:'Delete "'+text+'"?',
            icon:'warning',
            showCancelButton:true,
            confirmButtonText:'Yes'
        }).then(r=>{if(r.isConfirmed) $.post('master_delete_ajax.php',{id,type:'hoa'},()=>loadHOA($('#financial_year').val()));});
    });

    $('#masterForm').submit(function(e){
        e.preventDefault();
        $.post('master_save_ajax.php', $(this).serialize(), function(resp){
            if(resp.status==='success'){ Swal.fire('Saved!',resp.message,'success').then(()=>location.reload()); }
            else Swal.fire('Error',resp.message,'error');
        },'json');
    });

    // Load HOA initially
    loadHOA($('#financial_year').val());

});
</script>

</body>
</html>
</script>

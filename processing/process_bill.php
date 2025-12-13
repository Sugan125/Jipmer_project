<?php
include '../config/db.php';
include '../includes/auth.php';
require_role(2);

$billId = intval($_POST['bill_id'] ?? 0);
if (!$billId) { header('Location: process_list.php'); exit; }

// Fetch bill details
$stmt = $conn->prepare("SELECT * FROM bill_entry WHERE Id = ?");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bill) { header('Location: process_list.php'); exit; }

$finYears = $conn->query("SELECT Id, FinYear FROM fin_year_master WHERE Status=1 ORDER BY FinYear DESC")
->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../header/header_processing.php'; ?>

<div class="container mt-4">
    <div class="card shadow rounded">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-file-invoice"></i> Process Bill #<?= htmlspecialchars($bill['BillNo']) ?></h4>
        </div>
        <div class="card-body">
            <form id="processBillForm">
                <input type="hidden" name="bill_id" value="<?= $billId ?>">

                <!-- Bill Info -->
                <div class="mb-3"><strong>Bill No:</strong> <?= htmlspecialchars($bill['BillNo']) ?></div>
                <div class="mb-3"><strong>Received:</strong> <?= date('d/m/Y', strtotime($bill['BillReceivedDate'])) ?></div>

                <!-- Financial Year -->
                <div class="mb-3">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year" class="form-select" required>
                    <?php foreach($finYears as $fy): ?>
                    <option value="<?= $fy['Id'] ?>"><?= $fy['FinYear'] ?></option>
                    <?php endforeach; ?>
                    </select>

                </div>

                <!-- HOA -->
                <div class="mb-3">
                    <label class="form-label">HOA</label>
                    <select name="hoa" id="hoa" class="form-select" required>
                        <option value="">Select HOA</option>
                        <!-- Options loaded dynamically via AJAX -->
                    </select>
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

<!-- JS Libraries -->
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){

    // Function to load HOA based on selected Financial Year
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
                    let selected = hoa.Id == selectedHOA ? 'selected' : '';
                    options += `<option value="${hoa.Id}" ${selected}>${hoa.FullHOA}</option>`;
                });
                $('#hoa').html(options);
            },
            error: function(){
                Swal.fire('Error', 'Could not load HOA options', 'error');
            }
        });
    }

    // Load HOA on page load
    loadHOA($('#financial_year').val());

    // Load HOA when Financial Year changes
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

});
</script>

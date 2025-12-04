<?php
include '../config/db.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit(); }

// Fetch employee master
$employees = $conn->query("SELECT Id, EmployeeName FROM employee_master")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill Entry</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .card { border-radius: 18px; }
        .hoa-box { background:#f8f9fa; border-radius:8px; padding:12px; }
    </style>
</head>
<body class="bg-light">

<?php include '../header/header.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-lg p-4">
                <h3 class="text-center mb-4 text-primary">
                    <i class="fas fa-file-invoice"></i> Bill Entry
                </h3>

                <!-- BILL ENTRY FORM -->
                <form id="billForm">

                    <div class="mb-3">
                        <label class="form-label">Bill Number <span class="text-danger">*</span></label>
                        <input type="text" name="billno" class="form-control" required>
                    </div>

                    <div class="fw-bold mt-3">Head of Account</div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-4"><input type="text" name="major" class="form-control" placeholder="Major Head"></div>
                        <div class="col-md-4"><input type="text" name="submajor" class="form-control" placeholder="Sub-Major Head"></div>
                        <div class="col-md-4"><input type="text" name="minor" class="form-control" placeholder="Minor Head"></div>
                        <div class="col-md-4"><input type="text" name="subminor" class="form-control" placeholder="Sub-Minor Head"></div>
                        <div class="col-md-4"><input type="text" name="detail" class="form-control" placeholder="Detail Head"></div>
                        <div class="col-md-4"><input type="text" name="object" class="form-control" placeholder="Object Head"></div>
                    </div>

                    <label class="mt-3 fw-semibold">Full Head of Account Preview:</label>
                    <div id="fullHOA" class="hoa-box text-secondary"></div>

                    <div class="mt-3">
                        <label class="form-label">Alloted To <span class="text-danger">*</span></label>
                        <select name="alloted" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                                <option value="<?= $emp['Id'] ?>"><?= ucwords(htmlspecialchars($emp['EmployeeName'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Pass">Pass</option>
                            <option value="Return">Return</option>
                        </select>
                    </div>

                    <div id="remarksDiv" style="display:none;">
                        <label class="form-label mt-3">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-4 py-2">
                            <i class="fas fa-check"></i> Submit
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// ⭐ HOA Preview
$('#billForm input[name="major"], #billForm input[name="submajor"], #billForm input[name="minor"], #billForm input[name="subminor"], #billForm input[name="detail"], #billForm input[name="object"]')
.on('input', function() {
    let full = '';
    
    // Loop only over HOA inputs
    $('#billForm input[name="major"], #billForm input[name="submajor"], #billForm input[name="minor"], #billForm input[name="subminor"], #billForm input[name="detail"], #billForm input[name="object"]').each(function(){
        if($(this).val().trim() !== '') full += $(this).val().trim() + " - ";
    });
    
    // Remove the trailing " - " and update preview
    $("#fullHOA").text(full.slice(0, -3));
});

// ⭐ Show Remarks
$('#status').change(function() {
    if ($(this).val() === 'Return') {
        $('#remarksDiv').slideDown();
    } else {
        $('#remarksDiv').slideUp();
        $('textarea[name="remarks"]').val('');
    }
});

// ⭐ AJAX Submit + SweetAlert
$("#billForm").on("submit", function(e){
    e.preventDefault();

    $.ajax({
        url: "bill_entry_submit.php",
        type: "POST",
        data: $(this).serialize(),
        success: function(response){
            Swal.fire({
                icon: 'success',
                title: 'Saved Successfully!',
                text: 'Bill Entry saved successfully.',
                confirmButtonColor: '#3085d6'
            });

            $("#billForm")[0].reset();
            $("#fullHOA").text('');
            $("#remarksDiv").hide();
        },
        error: function(){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong!'
            });
        }
    });
});
</script>

</body>
</html>

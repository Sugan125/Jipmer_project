<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

function getFinancialYears($count = 2) {
    $years = [];
    $currentYear = date('Y');
    $currentMonth = date('n');
    $startYear = ($currentMonth >= 4) ? $currentYear : $currentYear - 1;

    for ($i = 0; $i < $count; $i++) {
        $years[] = ($startYear + $i) . '-' . ($startYear + $i + 1);
    }
    return $years;
}

$finYears = getFinancialYears();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>

    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/all.min.css">

    <!-- Custom soft-theme styles -->
    <link rel="stylesheet" href="../../css/style.css">

    <script src="../../js/jquery-3.7.1.min.js"></script>
    <script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../../js/sweetalert2.all.min.js"></script>
    
<!-- Custom CSS for elegant pastel UI -->
<style>

.soft-input {
    border-radius: 8px !important;
    border: 1px solid #d3cde7 !important;
    padding: 10px;
}

.soft-input:focus {
    border-color:#9575cd !important;
    box-shadow:0 0 5px rgba(149,117,205,0.6) !important;
}
</style>

</head>

<body class="soft-bg">
<?php include '../../header/header_admin.php'; ?>

<div class="container" style="max-width: 900px; margin-top:60px;">

    <div class="hoa-form-card">

        <div class="hoa-header mb-3">
            <h4 class="mb-0"><i class="fa-solid fa-file-circle-plus me-2"></i>Add Head of Account</h4>
        </div>

        <form id="hoaForm">

            <div class="row g-3">

                <div class="mb-3">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year" id="financial_year" class="form-select soft-input" required>
                        <?php
                        $currentYear = date('Y');
                        $years = [
                            ($currentYear-1).'-'.substr($currentYear, 2),
                            $currentYear.'-'.substr($currentYear+1, 2),
                            ($currentYear+1).'-'.substr($currentYear+2, 2)
                        ];
                        foreach($years as $fy) {
                            echo "<option value='$fy'>$fy</option>";
                        }
                        ?>
                        </select>

                </div>

                <div class="col-md-4">
                    <label class="form-label">Major Head *</label>
                    <input type="text" name="major" class="form-control soft-input" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sub-Major Head</label>
                    <input type="text" name="submajor" class="form-control soft-input">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Minor Head</label>
                    <input type="text" name="minor" class="form-control soft-input">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sub-Minor Head</label>
                    <input type="text" name="subminor" class="form-control soft-input">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Detail Head</label>
                    <input type="text" name="detail" class="form-control soft-input">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Object Head</label>
                    <input type="text" name="object" class="form-control soft-input">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Description / Purpose</label>
                    <textarea name="description" class="form-control soft-input" rows="3"></textarea>
                </div>
            </div>

           <div class="mt-3 text-end">
    <!-- Save button -->
    <button type="submit" class="btn btn-primary btn-sm" style="background:#9575cd; border:none;">
        <i class="fa-solid fa-floppy-disk me-1"></i> Save HOA
    </button>

    <!-- View HOA List button (small, secondary) -->
    <a href="hoa_list.php" class="btn btn-secondary btn-sm ms-2">
        <i class="fa-solid fa-eye me-1"></i> View List
    </a>

    <!-- Cancel button -->
   <button type="reset" class="btn btn-outline-secondary btn-sm ms-2">
    <i class="fa-solid fa-arrow-left me-1"></i> Cancel
</button>
</div>

        </form>

    </div>

</div>

<!-- JS -->
<script src="../../js/jquery-3.7.1.min.js"></script>
<script src="../../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){

    $("#hoaForm").on("submit", function(e){
        e.preventDefault();

        $.ajax({
            url: "save_hoa_ajax.php",
            type: "POST",
            data: $("#hoaForm").serialize(),
            dataType: "json",

            success: function(res){
                if(res.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved Successfully!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    $("#hoaForm")[0].reset();
                }
                else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: res.message
                    });
                }
            },

            error: function(){
                Swal.fire("Error","Server issue occurred","error");
            }
        });
    });

});
</script>

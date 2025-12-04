<?php
include '../config/db.php';

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) { header('Location: login.php'); exit(); }

$user = $_SESSION['username'];

// Fetch Bill Numbers - ONLY PASSED STATUS
$billNumbers = $conn->query("SELECT Id, BillNo FROM bill_entry WHERE Status = 'Pass' ORDER BY BillNo")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher Entry - JIPMER</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .card-form {
            max-width: 650px;
            margin: auto;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-light">

<?php include '../header/header.php'; ?>

<div class="container" style="margin-top:130px;">
    <div class="text-center mb-4">
        <h2 class="fw-bold"><i class="fas fa-file-alt text-primary"></i> Voucher Entry</h2>
        <p class="text-muted">Enter voucher details linked with bill number</p>
    </div>

    <div class="card shadow-lg p-4 card-form">
        <form id="voucherForm">

            <div class="mb-3">
                <label class="form-label fw-semibold">Bill Number</label>
                <select name="billno" class="form-select" required>
                    <option value="">Select Bill Number</option>
                    <?php foreach($billNumbers as $bill): ?>
                        <option value="<?= $bill['Id'] ?>"><?= htmlspecialchars($bill['BillNo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Voucher Number</label>
                <input type="text" name="voucher" class="form-control" placeholder="Enter Voucher Number" required>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Voucher
                </button>
            </div>

        </form>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$("#voucherForm").on("submit", function(e){
    e.preventDefault();

    let formData = $(this).serialize();

    $.ajax({
        url: "voucher_submit.php",
        type: "POST",
        data: formData,
        dataType: "json",
        success: function(response){

            if (response.status === "bill_exists") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Already Entered!',
                    html: `Voucher entry already exists for this bill.<br><b>Voucher No: ${response.voucher}</b>`
                });
                return;
            }

            if (response.status === "voucher_exists") {
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Voucher!',
                    text: 'This voucher number already exists.'
                });
                return;
            }

            if (response.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Voucher saved successfully!'
                });
                $("#voucherForm")[0].reset();
                return;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: response.message || 'Unexpected error occurred'
            });
        },

        error: function(){
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error while saving voucher!'
            });
        }
    });
});
</script>

</body>
</html>

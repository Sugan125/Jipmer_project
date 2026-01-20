<?php
include '../config/db.php';
include '../includes/auth.php';

$billId = (int)($_GET['id'] ?? 0);

if ($billId <= 0) {
    die("Invalid Bill");
}

// Fetch bill + concerned section reply
$stmt = $conn->prepare("
    SELECT 
        bi.BillNumber,
        bi.BillReceivedDate,
        bi.TotalAmount AS Amount,
        be.TokenNo,
        be.Remarks,
        be.reviewed,
        bp.ReasonForReturn,
        cs.ReplyText
    FROM bill_entry be
    INNER JOIN bill_initial_entry bi ON bi.Id = be.BillInitialId
    LEFT JOIN bill_process bp ON bp.BillId = be.BillInitialId
    LEFT JOIN concerned_section_reply cs ON cs.BillId = be.Id
    WHERE be.Id = ?
");
$stmt->execute([$billId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    die("No returned bill found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Returned Bill Preview</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

    <script src="../js/jquery-3.7.1.min.js"></script>
    <script src="../js/sweetalert2.all.min.js"></script>

    <style>
        .main-content {
            margin-left: 260px;
            margin-top: 10px;
            padding: 20px;
        }
        .memo-wrapper {
            background: #fff;
            border: 1px solid #000;
            max-width: 900px;
            margin: auto;
            padding: 25px;
            font-size: 14px;
            color: #000;
        }
        .memo-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .memo-header h6 {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .memo-header small { display: block; }
        .memo-title {
            text-align: center;
            font-weight: 700;
            text-decoration: underline;
            margin: 15px 0;
        }
        .memo-table {
            width: 100%;
            border-collapse: collapse;
        }
        .memo-table th, .memo-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        .memo-table th { font-weight: 600; }
        .sign-box {
            height: 90px;
            vertical-align: bottom;
            font-size: 13px;
        }
        @media print {
            .sidebar, .navbar, .no-print { display: none !important; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php
require realpath(__DIR__ . '/../layout/topbar.php');
require realpath(__DIR__ . '/../layout/sidebar.php');
?>

<div class="main-content">
    <div class="memo-wrapper">

        <!-- HEADER -->
        <div class="memo-header d-flex align-items-center justify-content-between">
            <div>
                <img src="<?= BASE_URL ?>/images/enblum.png" alt="Emblem" style="height:70px;">
            </div>
            <div class="text-center flex-grow-1">
                <h6>JAWAHARLAL INSTITUTE OF POST GRADUATE MEDICAL EDUCATION AND RESEARCH, PUDUCHERRY - 6</h6>
                <small>(Institution of National Importance under Ministry of Health & Family Welfare, Government of India)</small>
                <small>Dhanwantari Nagar, Puducherry – 605006</small>
                <small>Website : <u>www.jipmer.edu.in</u></small>
                <small>Phone : 0413 - 2296038  Fax: 0413 - 2272067  - 227235</small>
            </div>
            <div>
                <img src="<?= BASE_URL ?>/images/logo.png" alt="JIPMER Logo" style="height:70px;">
            </div>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <div><strong>No.</strong> JIP/ReturnMemo/<?= date('Y') ?></div>
            <div><strong>Date:</strong> <?= date('d-m-Y') ?></div>
        </div>

        <div class="memo-title">RETURN MEMO - Preview & Review</div>

        <p>
            <strong>Sub:</strong> Return of Bill for Clarification / Remarks / Correction – Reg.<br><br>
            The below mentioned bill is returned herewith for necessary correction / remarks / clarification.
            Kindly review, edit if needed, and submit to enable resubmit.
        </p>

        <!-- BILL DETAILS -->
        <table class="memo-table mb-3">
            <tr>
                <th width="30%">1. Bill No</th>
                <td><?= htmlspecialchars($bill['BillNumber']) ?></td>
            </tr>
            <tr>
                <th>2. Token No</th>
                <td><?= htmlspecialchars($bill['TokenNo']) ?></td>
            </tr>
            <tr>
                <th>3. Bill Received Date</th>
                <td><?= !empty($bill['BillReceivedDate']) ? date('d-m-Y', strtotime($bill['BillReceivedDate'])) : '-' ?></td>
            </tr>
            <tr>
                <th>4. Remarks</th>
                <td><textarea name="remarks" class="form-control"><?= htmlspecialchars($bill['Remarks']) ?></textarea></td>
            </tr>
            <tr>
                <th>5. Bill Amount</th>
                <td><?= htmlspecialchars($bill['Amount']) ?></td>
            </tr>
        </table>

        <!-- REASON & REPLY -->
        <form id="previewForm">
            <input type="hidden" name="bill_id" value="<?= $billId ?>">

            <table class="memo-table">
                <tr>
                    <th width="50%">Reason for Returning Bill</th>
                    <th width="50%">Reply of the Concerned Section<span style="color:red">*</span></th>
                </tr>
                <tr>
                    <td><textarea name="reason" style="width:100%; height:120px;" required><?= htmlspecialchars($bill['ReasonForReturn']) ?></textarea></td>
                    <td><textarea name="reply" style="width:100%; height:120px;" required><?= htmlspecialchars($bill['ReplyText']) ?></textarea></td>
                </tr>
                <tr>
                    <td class="sign-box">
                        Signature of Section In-charge<br>
                        Bill Passing Section<br>
                        Date:
                    </td>
                    <td class="sign-box">
                        Signature of Section In-charge<br>
                        Concerned Section<br>
                        Date:
                    </td>
                </tr>
            </table>

            <div class="text-center mt-4 no-print">
                <button type="submit" class="btn btn-success px-4">
                    Submit Review & Enable Resubmit
                </button>
                <a href="returned_bills.php" class="btn btn-secondary px-4">Cancel</a>
                <button type="button" onclick="window.print()" class="btn btn-outline-dark px-4">Print</button>
            </div>
        </form>
    </div>
</div>

<script>
$('#previewForm').submit(function(e){
    e.preventDefault();
    $.post('returned_bill_preview_submit.php', $(this).serialize())
    .done(function(res){
        if(res.status === 'success'){
            Swal.fire('Success','Bill reviewed successfully','success')
                .then(()=>window.location.href='returned_bills.php');
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    })
    .fail(function(){
        Swal.fire('Error','Server error','error');
    });
});
</script>

</body>
</html>

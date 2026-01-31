<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';
include '../includes/auth.php';

$billId = (int)($_POST['bill_id'] ?? 0);
if ($billId <= 0) {
    die('<script>alert("Invalid Bill ID"); window.location.href="process_list.php";</script>');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($v){ return number_format((float)($v ?? 0), 2); }

/* ===========================
   BILL MASTER + LATEST ENTRY
   =========================== */
$billStmt = $conn->prepare("
    WITH LatestEntry AS (
        SELECT
            be.*,
            ROW_NUMBER() OVER (PARTITION BY be.BillInitialId ORDER BY be.Id DESC) AS rn
        FROM bill_entry be
    ),
    InvoiceAgg AS (
        SELECT
            bim.BillInitialId,
            COUNT(*) AS InvoiceCount,
            STRING_AGG(im.InvoiceNo, ', ') AS InvoiceNos,
            STRING_AGG(im.VendorName, ', ') AS Vendors,
            MAX(im.ReceivedFromSection) AS ReceivedFromSection,
            MAX(im.BillTypeId) AS BillTypeId,

            SUM(ISNULL(im.Amount,0)) AS SumBase,
            SUM(ISNULL(im.TotalAmount,0)) AS SumTotal,
            SUM(ISNULL(im.TDSGSTAmount,0)) AS SumTDSGST,
            SUM(ISNULL(im.TDSITAmount,0)) AS SumTDSIT,
            SUM(ISNULL(im.TDS,0)) AS SumTDS,
            SUM(ISNULL(im.NetPayable,0)) AS SumNet
        FROM bill_invoice_map bim
        JOIN invoice_master im ON im.Id = bim.InvoiceId
        WHERE bim.BillInitialId = ?
        GROUP BY bim.BillInitialId
    )
    SELECT
        bi.Id,
        bi.BillNumber,
        bi.BillReceivedDate,
        bi.CreatedDate,
        bi.Status AS BillInitStatus,

        le.Status AS EntryStatus,
        le.TokenNo,
        le.AllotedDate,
        le.Remarks AS EntryRemarks,
        emp.EmployeeName AS AllotedName,

        ia.InvoiceCount,
        ia.InvoiceNos,
        ia.Vendors,
        ia.ReceivedFromSection,
        btm.BillType,

        ia.SumBase,
        ia.SumTotal,
        ia.SumTDSGST,
        ia.SumTDSIT,
        ia.SumTDS,
        ia.SumNet
    FROM bill_initial_entry bi
    LEFT JOIN LatestEntry le ON le.BillInitialId = bi.Id AND le.rn = 1
    LEFT JOIN employee_master emp ON emp.Id = le.AllotedDealingAsst
    LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = bi.Id
    LEFT JOIN bill_type_master btm ON btm.Id = ia.BillTypeId
    WHERE bi.Id = ?
");
$billStmt->execute([$billId, $billId]);
$bill = $billStmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    die('<script>alert("Bill not found!"); window.location.href="process_list.php";</script>');
}

/* ===========================
   INVOICES (summary list)
   =========================== */
$invStmt = $conn->prepare("
    SELECT
        im.Id,
        im.InvoiceNo,
        im.InvoiceDate,
        im.VendorName,
        d.DeptName,
        im.ReceivedFromSection,

        ISNULL(im.Amount,0) AS Amount,
        ISNULL(im.TotalAmount,0) AS TotalAmount,
        ISNULL(im.TDSGSTAmount,0) AS TDSGSTAmount,
        ISNULL(im.TDSITAmount,0) AS TDSITAmount,
        ISNULL(im.TDS,0) AS TDS,
        ISNULL(im.NetPayable,0) AS NetPayable
    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    LEFT JOIN dept_master d ON d.Id = im.DeptId
    WHERE bim.BillInitialId = ?
    ORDER BY im.InvoiceDate DESC, im.Id DESC
");
$invStmt->execute([$billId]);
$invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

$totalNet = 0;
foreach($invoices as $x){ $totalNet += (float)$x['NetPayable']; }
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
body { margin:0; min-height:100vh; background:#f8f9fa; }
.page-content { margin-left:240px; padding:80px 20px 20px; }
.card { box-shadow:0 0.5rem 1rem rgba(0,0,0,.15); }
.invoice-table th, .invoice-table td { vertical-align: middle; }
.invoice-table tbody tr:hover { background:#f9f9f9; }

.inv-details-row { display:none; }
.inv-details-box{
    background:#fbfcff;
    border:1px solid #e7ecff;
    border-radius:12px;
    padding:12px;
}

.small-h{
    font-size:12px; color:#6c757d; font-weight:700;
    text-transform:uppercase; letter-spacing:.3px;
}
</style>
</head>
<body>

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

    <!-- BILL HEADER (DETAILED) -->
    <div class="card p-4 mb-4">
        <h4 class="text-primary mb-3">
            <i class="fas fa-file-invoice"></i>
            Process Bill #<?= h($bill['BillNumber'] ?? '-') ?>
        </h4>

        <div class="row g-2">
            <div class="col-md-3"><b>Received Date:</b> <?= !empty($bill['BillReceivedDate']) ? date('d-m-Y', strtotime($bill['BillReceivedDate'])) : '-' ?></div>
            <div class="col-md-3"><b>From Section:</b> <?= h($bill['ReceivedFromSection'] ?? '-') ?></div>
            <div class="col-md-3"><b>Bill Type:</b> <?= h($bill['BillType'] ?? '-') ?></div>
            <div class="col-md-3">
                <b>Status:</b>
                <span class="badge bg-info"><?= h($bill['EntryStatus'] ?? 'Draft') ?></span>
            </div>

            <div class="col-md-3"><b>Token No:</b> <?= h($bill['TokenNo'] ?? '-') ?></div>
            <div class="col-md-3"><b>Alloted To:</b> <?= h($bill['AllotedName'] ?? '-') ?></div>
            <div class="col-md-3"><b>Alloted Date:</b> <?= !empty($bill['AllotedDate']) ? date('d-m-Y', strtotime($bill['AllotedDate'])) : '-' ?></div>
            <div class="col-md-3"><b>Invoice Count:</b> <?= (int)($bill['InvoiceCount'] ?? 0) ?></div>

            <div class="col-md-3"><b>Base Total:</b> ₹ <?= nf($bill['SumBase'] ?? 0) ?></div>
            <div class="col-md-3"><b>Gross Total:</b> ₹ <?= nf($bill['SumTotal'] ?? 0) ?></div>
            <div class="col-md-3"><b>TDS GST:</b> ₹ <?= nf($bill['SumTDSGST'] ?? 0) ?></div>
            <div class="col-md-3"><b>TDS IT:</b> ₹ <?= nf($bill['SumTDSIT'] ?? 0) ?></div>

            <div class="col-md-3"><b>Total TDS:</b> ₹ <?= nf($bill['SumTDS'] ?? 0) ?></div>
            <div class="col-md-3"><b>Total Net:</b> ₹ <span class="text-success fw-bold"><?= nf($bill['SumNet'] ?? 0) ?></span></div>

            <div class="col-md-6">
                <b>Invoices:</b>
                <div class="text-muted small"><?= h($bill['InvoiceNos'] ?? '-') ?></div>
            </div>
            <div class="col-md-6">
                <b>Vendors:</b>
                <div class="text-muted small"><?= h($bill['Vendors'] ?? '-') ?></div>
            </div>
        </div>

        <?php if(!empty($bill['EntryRemarks'])): ?>
        <hr>
        <div><b>Last Remarks:</b><br><?= nl2br(h($bill['EntryRemarks'])) ?></div>
        <?php endif; ?>
    </div>

    <!-- INVOICE LIST -->
    <div class="card p-4 mb-4">
        <h5 class="text-secondary mb-3"><i class="fas fa-receipt"></i> Attached Invoices</h5>

        <?php if($invoices): ?>
        <div class="table-responsive">
        <table class="table table-bordered table-striped invoice-table">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Invoice</th>
                    <th>Vendor / Dept</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">TDS GST</th>
                    <th class="text-end">TDS IT</th>
                    <th class="text-end">TDS</th>
                    <th class="text-end">Net</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($invoices as $i => $inv): ?>
                <tr>
                    <td><?= $i+1 ?></td>

                    <td>
                        <div class="fw-bold"><?= h($inv['InvoiceNo']) ?></div>
                        <div class="small text-muted"><?= date('d-m-Y', strtotime($inv['InvoiceDate'])) ?></div>
                        <div class="small text-muted">From: <?= h($inv['ReceivedFromSection'] ?? '-') ?></div>
                    </td>

                    <td>
                        <div class="fw-bold"><?= h($inv['VendorName']) ?></div>
                        <div class="small text-muted"><?= h($inv['DeptName'] ?? '-') ?></div>
                    </td>

                    <td class="text-end fw-bold"><?= nf($inv['TotalAmount']) ?></td>
                    <td class="text-end text-danger"><?= nf($inv['TDSGSTAmount']) ?></td>
                    <td class="text-end text-danger"><?= nf($inv['TDSITAmount']) ?></td>
                    <td class="text-end text-danger"><?= nf($inv['TDS']) ?></td>
                    <td class="text-end fw-bold text-success"><?= nf($inv['NetPayable']) ?></td>

                    <td class="text-nowrap">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary inv-view"
                                data-id="<?= (int)$inv['Id'] ?>">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </td>
                </tr>

                <!-- EXPAND ROW -->
                <tr class="inv-details-row">
                    <td colspan="9">
                        <div class="inv-details-box">
                            <div id="invBox<?= (int)$inv['Id'] ?>" data-loaded="0">
                                <div class="text-center text-muted py-3">Click view to load details...</div>
                            </div>
                        </div>
                    </td>
                </tr>

            <?php endforeach; ?>
            </tbody>

            <tfoot class="table-light">
                <tr>
                    <td colspan="8" class="text-end fw-bold">Total Net Payable</td>
                    <td class="text-end fw-bold text-success"><?= nf($totalNet) ?></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <?php else: ?>
            <p class="text-muted">No invoices attached.</p>
        <?php endif; ?>
    </div>

    <!-- PROCESS FORM -->
    <div class="card p-4">
        <form id="processBillForm">
            <input type="hidden" name="bill_id" value="<?= $billId ?>">

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="">Select Status</option>
                    <option value="Pass">Pass</option>
                    <option value="Return">Return</option>
                </select>
            </div>

            <div class="mb-3" id="returnReasonDiv" style="display:none;">
                <label class="form-label">Reason for Return</label>
                <textarea name="reason" class="form-control" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Save
            </button>
            <a href="process_list.php" class="btn btn-secondary">Back</a>
        </form>
    </div>

</div>

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(function(){

    // Expand invoice details (slide down)
    $('.inv-view').on('click', function(){
        const invoiceId = $(this).data('id');
        const tr = $(this).closest('tr');
        const detailsRow = tr.next('.inv-details-row');
        const box = $('#invBox' + invoiceId);

        // Toggle
        if(detailsRow.is(':visible')){
            detailsRow.find('.inv-details-box').slideUp(150, function(){
                detailsRow.hide();
            });
            return;
        }

        // Close others
        $('.inv-details-row:visible').each(function(){
            $(this).find('.inv-details-box').slideUp(150, () => $(this).hide());
        });

        detailsRow.show();
        detailsRow.find('.inv-details-box').hide().slideDown(200);

        // Load once
        if(box.data('loaded') == 1) return;

        box.html('<div class="text-center text-muted py-3">Loading invoice + PO + bank + sanctions...</div>');

        $.get('../receiving/invoice_details_expand.php', { id: invoiceId }, function(res){
            box.html(res);
            box.data('loaded', 1);
        }).fail(function(xhr){
            box.html('<div class="text-danger text-center py-3">Failed to load details. Check invoice_details_expand.php</div>');
        });
    });

    // Status -> Return reason show/hide
    $('#status').on('change', function(){
        if(this.value === 'Return'){
            $('#returnReasonDiv').show();
            $('#returnReasonDiv textarea').attr('required', true);
        } else {
            $('#returnReasonDiv').hide();
            $('#returnReasonDiv textarea').attr('required', false);
        }
    });

    // Save processing
    $('#processBillForm').on('submit', function(e){
        e.preventDefault();
        $.ajax({
            url: 'bill_process_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status === 'success'){
                    Swal.fire({ icon:'success', title:'Saved!', text: resp.message })
                    .then(()=> window.location.href='process_list.php');
                } else {
                    Swal.fire({ icon:'error', title:'Error', text: resp.message });
                }
            },
            error: function(){
                Swal.fire({ icon:'error', title:'Error', text:'Something went wrong!' });
            }
        });
    });

});
</script>

</body>
</html>

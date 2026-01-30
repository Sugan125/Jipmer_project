<?php
include '../config/db.php';
include '../includes/auth.php';

/* =========================
   BILL ENTRY PAGE (FULL)
   - Shows bill header
   - Shows invoices table
   - Click "View" to expand invoice details (PO + PO items + sanctions + bank)
   - Expand section slides down inside table (no modal)
   ========================= */

// Get bill id
$initial_id = intval($_GET['id'] ?? 0);
if(!$initial_id) exit('Invalid Bill ID');

// Fetch bill details
$stmt = $conn->prepare("SELECT * FROM bill_initial_entry WHERE Id=?");
$stmt->execute([$initial_id]);
$init = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$init){
    exit('Bill not found');
}

// Fetch attached invoices
$stmt = $conn->prepare("
    SELECT im.*, d.DeptName
    FROM invoice_master im
    JOIN bill_invoice_map bim ON bim.InvoiceId = im.Id
    LEFT JOIN dept_master d ON d.Id = im.DeptId
    WHERE bim.BillInitialId=?
    ORDER BY im.InvoiceDate DESC
");
$stmt->execute([$initial_id]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$billDate = $init['BillReceivedDate'] ?? date('Y-m-d');

// Fetch employees
$emps = $conn->query("
    SELECT Id, EmployeeName 
    FROM employee_master 
    WHERE Status=1 AND RoleId=2 
    ORDER BY EmployeeName
")->fetchAll(PDO::FETCH_ASSOC);

// Totals
$totalAmount = 0;
$totalGST    = 0;
$totalIT     = 0;
$totalTDS    = 0;
$grossTotal  = 0;
$netTotal    = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bill Entry</title>

<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">

<style>
.page-content{margin-left:240px;padding:60px 20px;}
.card-shadow{box-shadow:0 0.5rem 1rem rgba(0,0,0,.15);}
.invoice-table th, .invoice-table td{vertical-align: middle;}
.invoice-table tbody tr:hover{background:#f9f9f9;}
.btn-view{color:#0d6efd;}
.form-card{max-width:900px;margin:auto;}
.inv-details-row td{background:#fff;}
.inv-details-box{
    border:1px solid #e9ecef;
    border-radius:12px;
    background:#fbfcff;
}
</style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

<!-- Bill Header -->
<!-- Bill Header (MORE DETAILED) -->
<div class="card card-shadow p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="text-primary mb-1">
                <i class="fa fa-file-invoice"></i>
                Bill Details - <?= htmlspecialchars($init['BillNumber'] ?? '-') ?>
            </h4>
            <div class="text-muted small">
                Bill Initial ID: <b><?= (int)($init['Id'] ?? 0) ?></b>
                <?php if(!empty($init['CreatedDate'])): ?>
                    &nbsp; | &nbsp; Created: <b><?= date('d-m-Y H:i', strtotime($init['CreatedDate'])) ?></b>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-end">
            <?php
                $st = trim((string)($init['Status'] ?? ''));
                $badge = 'bg-secondary';
                if(strtolower($st) === 'pass') $badge = 'bg-success';
                elseif(strtolower($st) === 'reject') $badge = 'bg-danger';
                elseif(strtolower($st) === 'pending') $badge = 'bg-warning text-dark';
            ?>
            <div class="mb-1">
                <span class="badge <?= $badge ?> px-3 py-2" style="font-size:13px;">
                    <?= htmlspecialchars($st !== '' ? $st : 'Draft') ?>
                </span>
            </div>

            <div class="small">
                <?php if(!empty($init['BillReceivedDate'])): ?>
                    <span class="text-muted">Received:</span>
                    <b><?= date('d-m-Y', strtotime($init['BillReceivedDate'])) ?></b>
                <?php else: ?>
                    <span class="text-muted">Received:</span> <b>-</b>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <hr class="my-3">

    <div class="row g-3">
        <!-- Basic -->
        <div class="col-md-3">
            <div class="text-muted small">Bill Number</div>
            <div class="fw-bold fs-6"><?= htmlspecialchars($init['BillNumber'] ?? '-') ?></div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Received Date</div>
            <div class="fw-bold fs-6">
                <?= !empty($init['BillReceivedDate']) ? date('d-m-Y', strtotime($init['BillReceivedDate'])) : '-' ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="text-muted small">Received From Section</div>
            <div class="fw-bold fs-6"><?= htmlspecialchars($init['ReceivedFromSection'] ?? '-') ?></div>
        </div>

        <!-- Totals from bill_initial_entry if you have these columns -->
        <div class="col-md-3">
            <div class="text-muted small">Gross Total (Bill)</div>
            <div class="fw-bold">₹ <?= number_format((float)($init['GrossTotal'] ?? 0), 2) ?></div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Total TDS (Bill)</div>
            <div class="fw-bold text-danger">₹ <?= number_format((float)($init['TotalTDS'] ?? 0), 2) ?></div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Net Total (Bill)</div>
            <div class="fw-bold text-success">₹ <?= number_format((float)($init['NetTotal'] ?? 0), 2) ?></div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Invoices Attached</div>
            <div class="fw-bold"><?= isset($invoices) ? count($invoices) : 0 ?></div>
        </div>

        <!-- If bill_entry exists, show token & allot info (OPTIONAL: if you fetch it) -->
        <?php
        // OPTIONAL: fetch latest bill_entry for this bill to show token/allot info
        $be = [];
        try{
            $beStmt = $conn->prepare("
                SELECT TOP 1 be.TokenNo, be.Status AS EntryStatus, be.AllotedDealingAsst, be.AllotDate, be.Remarks, e.EmployeeName
                FROM bill_entry be
                LEFT JOIN employee_master e ON e.Id = be.AllotedDealingAsst
                WHERE be.BillInitialId = ?
                ORDER BY be.Id DESC
            ");
            $beStmt->execute([(int)$initial_id]);
            $be = $beStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }catch(Exception $ex){
            $be = [];
        }
        ?>

        <div class="col-md-3">
            <div class="text-muted small">Token No</div>
            <div class="fw-bold"><?= htmlspecialchars($be['TokenNo'] ?? '-') ?></div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Alloted To</div>
            <div class="fw-bold"><?= htmlspecialchars($be['EmployeeName'] ?? '-') ?></div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Allot Date</div>
            <div class="fw-bold">
                <?= !empty($be['AllotDate']) ? date('d-m-Y', strtotime($be['AllotDate'])) : '-' ?>
            </div>
        </div>

        <div class="col-md-3">
            <div class="text-muted small">Entry Status</div>
            <div class="fw-bold"><?= htmlspecialchars($be['EntryStatus'] ?? ($init['Status'] ?? 'Draft')) ?></div>
        </div>

        <?php if(!empty($be['Remarks'])): ?>
        <div class="col-12">
            <div class="text-muted small">Remarks</div>
            <div class="fw-bold"><?= nl2br(htmlspecialchars($be['Remarks'])) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Attached Invoices -->
<div class="card card-shadow p-4 mb-4">
    <h5 class="text-secondary mb-3"><i class="fa fa-receipt"></i> Attached Invoices</h5>

    <?php if($invoices): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped invoice-table">
            <thead class="table-light text-center">
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Invoice No</th>
                    <th style="width:110px;">Date</th>
                    <th>Vendor</th>
                    <th>Department</th>
                    <th style="width:140px;">Total Amount</th>
                    <th style="width:120px;">TDS GST</th>
                    <th style="width:120px;">TDS IT</th>
                    <th style="width:120px;">Total TDS</th>
                    <th style="width:140px;">Net Payable</th>
                    <th style="width:110px;">Actions</th>
                </tr>
            </thead>

            <tbody class="text-center">
            <?php foreach($invoices as $i => $inv): 
                $amount = floatval($inv['TotalAmount'] ?? 0);
                $gst    = floatval($inv['TDSGSTAmount'] ?? 0);
                $it     = floatval($inv['TDSITAmount'] ?? 0);
                $tds    = $gst + $it;
                $net    = floatval($inv['NetPayable'] ?? 0);

                $totalAmount += floatval($inv['Amount'] ?? 0);
                $totalGST    += $gst;
                $totalIT     += $it;
                $totalTDS    += $tds;
                $grossTotal  += $amount;
                $netTotal    += $net;
            ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></td>
                    <td><?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?></td>
                    <td class="text-start"><?= htmlspecialchars($inv['VendorName'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($inv['DeptName'] ?? '-') ?></td>
                    <td>₹ <?= number_format($amount,2) ?></td>
                    <td class="text-danger">₹ <?= number_format($gst,2) ?></td>
                    <td class="text-danger">₹ <?= number_format($it,2) ?></td>
                    <td class="text-danger">₹ <?= number_format($tds,2) ?></td>
                    <td class="text-success fw-bold">₹ <?= number_format($net,2) ?></td>
                    <td>
                        <button type="button"
                                class="btn btn-sm btn-outline-primary btn-view"
                                data-id="<?= (int)$inv['Id'] ?>">
                            <i class="fa fa-eye"></i> View
                        </button>
                    </td>
                </tr>

                <!-- IMPORTANT: Expand row -->
                <tr class="inv-details-row" style="display:none;">
                    <td colspan="11" class="p-0">
                        <div class="p-3 inv-details-box" id="invBox<?= (int)$inv['Id'] ?>" data-loaded="0">
                            <div class="text-center text-muted py-2">
                                Click "View" to load invoice details...
                            </div>
                        </div>
                    </td>
                </tr>

            <?php endforeach; ?>
            </tbody>

            <tfoot class="table-light text-center fw-bold">
                <tr>
                    <td colspan="5">Totals</td>
                    <td>₹ <?= number_format($grossTotal,2) ?></td>
                    <td class="text-danger">₹ <?= number_format($totalGST,2) ?></td>
                    <td class="text-danger">₹ <?= number_format($totalIT,2) ?></td>
                    <td class="text-danger">₹ <?= number_format($totalTDS,2) ?></td>
                    <td class="text-success">₹ <?= number_format($netTotal,2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
        <p class="text-muted">No invoices attached.</p>
    <?php endif; ?>
</div>

<!-- Bill Entry Form -->
<div class="card card-shadow p-4 mb-5 form-card">
    <h4 class="text-primary mb-4"><i class="fa fa-edit"></i> Record Bill Entry</h4>

    <form id="billForm">
        <input type="hidden" name="initial_id" value="<?= (int)$init['Id'] ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label"><i class="fa fa-hashtag"></i> Token No</label>
                <input type="text" name="tokno" class="form-control" placeholder="Enter token number" required>
            </div>

            <div class="col-md-6">
                <label class="form-label"><i class="fa fa-user"></i> Alloted Dealing Assistant</label>
                <select name="alloted" class="form-select" required>
                    <option value="">Select</option>
                    <?php foreach($emps as $e): ?>
                        <option value="<?= (int)$e['Id'] ?>"><?= htmlspecialchars($e['EmployeeName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label"><i class="fa fa-calendar-alt"></i> Allot Date</label>
                <input type="date"
                       name="allotdate"
                       class="form-control"
                       required
                       min="<?= date('Y-m-d', strtotime($billDate)) ?>"
                       value="<?= date('Y-m-d', strtotime($billDate)) ?>">
            </div>

            <div class="col-12">
                <label class="form-label"><i class="fa fa-sticky-note"></i> Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter remarks" required></textarea>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-between">
            <a href="bill_entry_list.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            <button class="btn btn-primary">
                <i class="fa fa-save"></i> Save Bill
            </button>
        </div>
    </form>
</div>

</div><!-- page-content -->

<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).on('click', '.btn-view', function(){
    const invoiceId = $(this).data('id');
    const tr = $(this).closest('tr');
    const detailsRow = tr.next('.inv-details-row');
    const box = $('#invBox' + invoiceId);

    if(detailsRow.length === 0){
        Swal.fire('Error', 'Details row not found. Please add inv-details-row after each invoice row.', 'error');
        return;
    }

    // Toggle close
    if(detailsRow.is(':visible')){
        box.slideUp(150, function(){ detailsRow.hide(); });
        return;
    }

    // Close others
    $('.inv-details-row:visible').each(function(){
        const b = $(this).find('.inv-details-box');
        b.slideUp(150, () => $(this).hide());
    });

    // Open this
    detailsRow.show();
    box.hide().slideDown(200);

    // Load once
    if(box.data('loaded') == 1) return;

    box.html('<div class="text-center text-muted py-3">Loading invoice + PO + sanctions...</div>');

    $.get('invoice_details_expand.php', { id: invoiceId }, function(res){
        box.html(res);
        box.data('loaded', 1);
    }).fail(function(xhr){
        box.html('<div class="text-danger text-center py-3">Failed to load: HTTP ' + xhr.status + '</div>');
    });
});

// Bill form submission
$("#billForm").on("submit", function(e){
    e.preventDefault();
    $.ajax({
        url: "bill_entry_submit.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function(r){
            if(r.status === "success"){
                Swal.fire({
                    icon: "success",
                    title: "Saved",
                    text: "Bill successfully recorded.",
                    timer: 1500,
                    showConfirmButton: false,
                    willClose: () => { window.location.href = "bill_entry_list.php"; }
                });
            } else {
                Swal.fire("Error", r.message || "Unknown error", "error");
            }
        },
        error: function(){
            Swal.fire("Server Error", "Unable to save right now.", "error");
        }
    });
});

// Allot date validation (no before bill date / no future)
$('input[name="allotdate"]').on('change', function(){
    const billDate = new Date('<?= date('Y-m-d', strtotime($billDate)) ?>');
    const today = new Date();
    const selected = new Date(this.value);

    // normalize today to date only
    today.setHours(0,0,0,0);

    if(selected < billDate){
        Swal.fire('Invalid Date', 'Allot Date cannot be before Bill Received Date', 'warning');
        this.value = '<?= date('Y-m-d', strtotime($billDate)) ?>';
    } else if(selected > today){
        Swal.fire('Invalid Date', 'Allot Date cannot be in the future', 'warning');
        this.value = '<?= date('Y-m-d', strtotime($billDate)) ?>';
    }
});
</script>

</body>
</html>

<?php
include '../config/db.php';
include '../includes/auth.php';

/* ============ AUTH CHECK (same pattern) ============ */
$page = basename($_SERVER['PHP_SELF']);
// $stmt = $conn->prepare("
//     SELECT COUNT(*)
//     FROM menu_master m
//     JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
//     WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
// ");
// $stmt->execute([$_SESSION['role'], "%$page%"]);
// if ($stmt->fetchColumn() == 0) die("Unauthorized Access");

/* ============ INPUT ============ */
$batchNo  = trim($_GET['batch'] ?? '');
$voucherNo= trim($_GET['voucher'] ?? '');
if($batchNo === '' || $voucherNo === '') die("Invalid Batch");

/* ============ HELPERS ============ */
function nf($v){ return number_format((float)($v ?? 0), 2); }

/* =========================================================
   1) BATCH SUMMARY (no invoice join duplication)
   ========================================================= */
$batchSumStmt = $conn->prepare("
    WITH BatchBills AS (
        SELECT bt.BatchNo, bt.VoucherNo, bt.BillId, bt.CreatedDate
        FROM bill_transactions bt
        WHERE bt.BatchNo = ? AND bt.VoucherNo = ?
    ),
    BillInit AS (
        SELECT bb.BatchNo, bb.VoucherNo, bb.BillId, bb.CreatedDate, b.BillInitialId
        FROM BatchBills bb
        JOIN bill_entry b ON b.Id = bb.BillId
    ),
    InvoiceAgg AS (
        SELECT
            bi2.BillInitialId,
            COUNT(*) AS InvoiceCount,
            SUM(ISNULL(im.NetPayable,0)) AS TotalNet
        FROM BillInit bi2
        JOIN bill_invoice_map bim ON bim.BillInitialId = bi2.BillInitialId
        JOIN invoice_master im ON im.Id = bim.InvoiceId
        GROUP BY bi2.BillInitialId
    )
    SELECT
        bi.BatchNo,
        bi.VoucherNo,
        COUNT(DISTINCT bi.BillId) AS BillCount,
        SUM(ISNULL(ia.TotalNet,0)) AS TotalAmount,
        MAX(bi.CreatedDate) AS CreatedDate
    FROM BillInit bi
    LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = bi.BillInitialId
    GROUP BY bi.BatchNo, bi.VoucherNo
");
$batchSumStmt->execute([$batchNo, $voucherNo]);
$batch = $batchSumStmt->fetch(PDO::FETCH_ASSOC);
if(!$batch) die("Batch not found");

/* =========================================================
   2) BILLS IN THIS BATCH (one row per bill_initial)
   ========================================================= */
$billsStmt = $conn->prepare("
    WITH BatchBills AS (
        SELECT bt.BillId, bt.BatchNo, bt.VoucherNo, bt.CreatedDate
        FROM bill_transactions bt
        WHERE bt.BatchNo = ? AND bt.VoucherNo = ?
    ),
    BillInit AS (
        SELECT
            bb.BillId, bb.BatchNo, bb.VoucherNo, bb.CreatedDate,
            b.BillInitialId,
            b.TokenNo,
            b.Status AS BillEntryStatus,
            b.AllotedDealingAsst,
            b.AllotedDate,
            b.Remarks AS BillEntryRemarks
        FROM BatchBills bb
        JOIN bill_entry b ON b.Id = bb.BillId
    ),
    InvoiceAgg AS (
        SELECT
            bim.BillInitialId,
            COUNT(*) AS InvoiceCount,
            MAX(im.VendorName) AS VendorName,
            MAX(im.ReceivedFromSection) AS ReceivedFromSection,
            SUM(ISNULL(im.TotalAmount,0)) AS TotalAmount,
            SUM(ISNULL(im.NetPayable,0)) AS TotalNetPayable
        FROM bill_invoice_map bim
        JOIN invoice_master im ON im.Id = bim.InvoiceId
        GROUP BY bim.BillInitialId
    )
    SELECT
        bi.BillId, bi.BillInitialId,
        ie.BillNumber, ie.BillReceivedDate, ie.Status AS InitialStatus,
        bi.TokenNo, bi.BillEntryStatus, bi.AllotedDate, bi.BillEntryRemarks,
        emp.EmployeeName AS AllotedName,

        ia.InvoiceCount,
        ia.VendorName,
        ia.ReceivedFromSection,
        ia.TotalAmount,
        ia.TotalNetPayable

    FROM BillInit bi
    JOIN bill_initial_entry ie ON ie.Id = bi.BillInitialId
    LEFT JOIN employee_master emp ON emp.Id = bi.AllotedDealingAsst
    LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = bi.BillInitialId
    ORDER BY ie.BillReceivedDate DESC, ie.Id DESC
");
$billsStmt->execute([$batchNo, $voucherNo]);
$bills = $billsStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   3) ALL INVOICES FOR THIS BATCH (detailed)
   We'll fetch in ONE query and group in PHP by BillInitialId
   ========================================================= */
$invStmt = $conn->prepare("
    SELECT
        bim.BillInitialId,
        i.Id AS InvoiceId,
        i.InvoiceNo,
        i.InvoiceDate,
        i.VendorName,
        d.DeptName,
        bt.BillType,

        ISNULL(i.Amount,0) AS Amount,
        ISNULL(i.TotalAmount,0) AS TotalAmount,
        ISNULL(i.TDSGSTPercent,0) AS TDSGSTPercent,
        ISNULL(i.TDSGSTAmount,0)  AS TDSGSTAmount,
        ISNULL(i.TDSITPercent,0)  AS TDSITPercent,
        ISNULL(i.TDSITAmount,0)   AS TDSITAmount,
        ISNULL(i.TDS,0)           AS TDS,
        ISNULL(i.NetPayable,0)    AS NetPayable,

        i.BankName, i.AccountNumber, i.IFSC, i.PanNumber, i.PFMSNumber,
        i.ReceivedFromSection,
        i.SectionDAName,

        (h.DetailsHeadCode + ' - ' + h.DetailsHeadName + ' / ' + h.SubDetailsHeadName) AS HOA_NAME,
        cr.CreditName,
        dr.DebitName,

        pm.POOrderNo,
        pm.POOrderDate,
        ISNULL(pm.POAmount,0) AS POAmount,
        ISNULL(pm.POGSTAmount,0) AS POGSTAmount,
        ISNULL(pm.POITAmount,0) AS POITAmount,
        ISNULL(pm.PONetAmount,0) AS PONetAmount,
        pm.GSTNumber,

        pb.pan_number AS PO_PAN,
        pb.pfms_number AS PO_PFMS,
        pb.bank_name AS PO_Bank,
        pb.ifsc AS PO_IFSC,
        pb.account_number AS PO_Account,

        st.SanctionNos

    FROM bill_invoice_map bim
    JOIN invoice_master i ON i.Id = bim.InvoiceId

    LEFT JOIN dept_master d ON d.Id = i.DeptId
    LEFT JOIN bill_type_master bt ON bt.Id = i.BillTypeId
    LEFT JOIN hoa_master h ON h.HoaId = i.HOAId
    LEFT JOIN account_credit_master cr ON cr.Id = i.CreditToId
    LEFT JOIN account_debit_master dr ON dr.Id = i.DebitFromId

    LEFT JOIN po_master pm ON pm.Id = i.POId
    OUTER APPLY (
        SELECT TOP 1 *
        FROM po_bank_details pbd
        WHERE pbd.po_id = i.POId AND pbd.is_active = 1
        ORDER BY pbd.id DESC
    ) pb

   OUTER APPLY (
    SELECT
        STUFF((
            SELECT DISTINCT ', ' + so2.SanctionOrderNo
            FROM invoice_sanction_map ism2
            JOIN sanction_order_master so2 ON so2.Id = ism2.SanctionId
            WHERE ism2.InvoiceId = i.Id
            FOR XML PATH(''), TYPE
        ).value('.','NVARCHAR(MAX)'), 1, 2, '') AS SanctionNos
) st


    WHERE bim.BillInitialId IN (
        SELECT b.BillInitialId
        FROM bill_transactions bt
        JOIN bill_entry b ON b.Id = bt.BillId
        WHERE bt.BatchNo = ? AND bt.VoucherNo = ?
    )
    ORDER BY bim.BillInitialId DESC, i.InvoiceDate DESC, i.Id DESC
");
$invStmt->execute([$batchNo, $voucherNo]);
$allInvoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

/* Group invoices by bill */
$invByBill = [];
foreach($allInvoices as $row){
    $bid = (int)$row['BillInitialId'];
    if(!isset($invByBill[$bid])) $invByBill[$bid] = [];
    $invByBill[$bid][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Batch Full View</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<style>
.page-content{margin-left:240px;padding:90px 20px}
.card-shadow{box-shadow:0 0.5rem 1rem rgba(0,0,0,.15)}
.kv small{color:#6c757d}
.table td,.table th{vertical-align:middle}
.mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
.badge-soft{background:#eef4ff;color:#0d6efd}
</style>
</head>

<body class="bg-light">
<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">

  <!-- Batch Header -->
  <div class="card card-shadow p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h4 class="mb-1 text-primary">
          <i class="fa fa-boxes-stacked"></i> Batch Full View
        </h4>
        <div class="text-muted">
          Batch: <span class="mono fw-bold"><?= htmlspecialchars($batchNo) ?></span> |
          Voucher: <span class="mono fw-bold"><?= htmlspecialchars($voucherNo) ?></span>
        </div>
      </div>
      <div class="text-end">
        <div><small class="text-muted">Created</small></div>
        <div class="fw-bold"><?= !empty($batch['CreatedDate']) ? date('d-m-Y H:i', strtotime($batch['CreatedDate'])) : '-' ?></div>
      </div>
    </div>

    <hr>

    <div class="row g-3 kv">
      <div class="col-md-3">
        <small>No. of Bills</small>
        <div class="fw-bold"><?= (int)$batch['BillCount'] ?></div>
      </div>
      <div class="col-md-3">
        <small>Total Amount (Net)</small>
        <div class="fw-bold text-success">₹ <?= nf($batch['TotalAmount']) ?></div>
      </div>
      <div class="col-md-6">
        <small>Note</small>
        <div class="text-muted">This page shows full batch → bill → invoice → PO → sanction details in one place.</div>
      </div>
    </div>
  </div>

  <!-- Bills Accordion -->
  <div class="accordion" id="billAcc">

    <?php if(empty($bills)): ?>
      <div class="alert alert-warning">No bills found under this batch.</div>
    <?php else: ?>

      <?php foreach($bills as $idx => $b): 
        $billInitialId = (int)$b['BillInitialId'];
        $collapseId = "c".$billInitialId;
        $headingId  = "h".$billInitialId;
        $invList = $invByBill[$billInitialId] ?? [];
      ?>
      <div class="accordion-item mb-3 border-0">
        <h2 class="accordion-header" id="<?= $headingId ?>">
          <button class="accordion-button collapsed card-shadow" type="button"
                  data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>"
                  aria-expanded="false" aria-controls="<?= $collapseId ?>">
            <div class="w-100">
              <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                  <span class="fw-bold">Bill:</span>
                  <span class="mono"><?= htmlspecialchars($b['BillNumber'] ?? '-') ?></span>
                  <span class="badge badge-soft ms-2"><?= htmlspecialchars($b['ReceivedFromSection'] ?? '-') ?></span>
                </div>
                <div class="text-end">
                  <span class="fw-bold text-success">₹ <?= nf($b['TotalNetPayable'] ?? 0) ?></span>
                  <span class="text-muted ms-2">(Invoices: <?= (int)($b['InvoiceCount'] ?? 0) ?>)</span>
                </div>
              </div>

              <div class="mt-1 small text-muted">
                Received: <?= !empty($b['BillReceivedDate']) ? date('d-m-Y', strtotime($b['BillReceivedDate'])) : '-' ?> |
                Token: <?= htmlspecialchars($b['TokenNo'] ?? '-') ?> |
                Status: <b><?= htmlspecialchars($b['BillEntryStatus'] ?? '-') ?></b> |
                Alloted: <?= htmlspecialchars($b['AllotedName'] ?? '-') ?> (<?= !empty($b['AllotedDate']) ? date('d-m-Y', strtotime($b['AllotedDate'])) : '-' ?>)
              </div>
            </div>
          </button>
        </h2>

        <div id="<?= $collapseId ?>" class="accordion-collapse collapse" aria-labelledby="<?= $headingId ?>" data-bs-parent="#billAcc">
          <div class="accordion-body bg-white card-shadow">

            <!-- Bill Remarks -->
            <?php if(!empty($b['BillEntryRemarks'])): ?>
              <div class="alert alert-light border">
                <b>Last Remarks:</b><br>
                <?= nl2br(htmlspecialchars($b['BillEntryRemarks'])) ?>
              </div>
            <?php endif; ?>

            <!-- Invoice Table -->
            <h6 class="text-primary mb-2"><i class="fa fa-receipt"></i> Invoices (with PO + Sanction)</h6>

            <?php if(empty($invList)): ?>
              <div class="text-muted">No invoices mapped for this bill.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                  <thead class="table-dark text-center">
                    <tr>
                      <th>#</th>
                      <th>Invoice</th>
                      <th>Vendor / Dept</th>
                      <th>HOA / Credit-Debit</th>
                      <th>PO</th>
                      <th>Sanctions (Mapped)</th>
                      <th class="text-end">Total</th>
                      <th class="text-end">TDS GST</th>
                      <th class="text-end">TDS IT</th>
                      <th class="text-end">Net</th>
                      <th>Bank</th>
                    </tr>
                  </thead>
                  <tbody class="text-center">
                  <?php foreach($invList as $k => $inv): ?>
                    <tr>
                      <td><?= $k+1 ?></td>

                      <td>
                        <div class="fw-bold"><?= htmlspecialchars($inv['InvoiceNo'] ?? '-') ?></div>
                        <div class="small text-muted">
                          <?= !empty($inv['InvoiceDate']) ? date('d-m-Y', strtotime($inv['InvoiceDate'])) : '-' ?>
                        </div>
                        <div class="small">
                          <span class="badge bg-light text-dark"><?= htmlspecialchars($inv['BillType'] ?? '-') ?></span>
                        </div>
                      </td>

                      <td>
                        <div class="fw-bold"><?= htmlspecialchars($inv['VendorName'] ?? '-') ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($inv['DeptName'] ?? '-') ?></div>
                        <div class="small">From: <?= htmlspecialchars($inv['ReceivedFromSection'] ?? '-') ?></div>
                        <div class="small">DA: <?= htmlspecialchars($inv['SectionDAName'] ?? '-') ?></div>
                      </td>

                      <td style="min-width:260px; text-align:left;">
                        <div><?= htmlspecialchars($inv['HOA_NAME'] ?? '-') ?></div>
                        <div class="small text-muted">
                          Credit: <?= htmlspecialchars($inv['CreditName'] ?? '-') ?> |
                          Debit: <?= htmlspecialchars($inv['DebitName'] ?? '-') ?>
                        </div>
                      </td>

                      <td style="min-width:220px; text-align:left;">
                        <div class="fw-bold"><?= htmlspecialchars($inv['POOrderNo'] ?? '-') ?></div>
                        <div class="small text-muted"><?= !empty($inv['POOrderDate']) ? date('d-m-Y', strtotime($inv['POOrderDate'])) : '-' ?></div>
                        <div class="small">GST No: <?= htmlspecialchars($inv['GSTNumber'] ?? '-') ?></div>
                        <div class="small">Base: ₹ <?= nf($inv['POAmount'] ?? 0) ?></div>
                        <div class="small text-muted">PO Net: ₹ <?= nf($inv['PONetAmount'] ?? 0) ?></div>
                      </td>

                      <td style="min-width:220px; text-align:left;">
                        <div class="small"><b>Mapped:</b></div>
                        <div><?= htmlspecialchars($inv['SanctionNos'] ?? '-') ?></div>
                      </td>

                      <td class="text-end fw-bold">₹ <?= nf($inv['TotalAmount'] ?? 0) ?></td>
                      <td class="text-end text-danger">₹ <?= nf($inv['TDSGSTAmount'] ?? 0) ?></td>
                      <td class="text-end text-danger">₹ <?= nf($inv['TDSITAmount'] ?? 0) ?></td>
                      <td class="text-end fw-bold text-success">₹ <?= nf($inv['NetPayable'] ?? 0) ?></td>

                      <td style="min-width:240px; text-align:left;">
                        <div><b><?= htmlspecialchars($inv['PO_Bank'] ?? $inv['BankName'] ?? '-') ?></b></div>
                        <div class="small">A/C: <?= htmlspecialchars($inv['PO_Account'] ?? $inv['AccountNumber'] ?? '-') ?></div>
                        <div class="small">IFSC: <?= htmlspecialchars($inv['PO_IFSC'] ?? $inv['IFSC'] ?? '-') ?></div>
                        <div class="small">PAN: <?= htmlspecialchars($inv['PO_PAN'] ?? $inv['PanNumber'] ?? '-') ?></div>
                        <div class="small">PFMS: <?= htmlspecialchars($inv['PO_PFMS'] ?? $inv['PFMSNumber'] ?? '-') ?></div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
      <?php endforeach; ?>

    <?php endif; ?>

  </div>

</div>

<!-- JS (IMPORTANT ORDER to avoid "$ is not defined") -->
<script src="../js/jquery-3.7.1.min.js"></script>
<script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>

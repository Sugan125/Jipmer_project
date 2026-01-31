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
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nf($v){ return number_format((float)($v ?? 0), 2); }

/*
  IMPORTANT:
  - bill_entry has multiple rows per BillInitialId sometimes.
  - We want ONLY the latest returned entry per bill, so use ROW_NUMBER().
  - InvoiceAgg gives invoice summary per bill.
*/
$sql = "
WITH LatestReturn AS (
    SELECT
        be.*,
        ROW_NUMBER() OVER (PARTITION BY be.BillInitialId ORDER BY be.Id DESC) AS rn
    FROM bill_entry be
    WHERE be.Status = 'Return'
),
InvoiceAgg AS (
    SELECT
        bim.BillInitialId,
        COUNT(*) AS InvoiceCount,
        MAX(im.SectionDAName) AS SectionDAName,
        MAX(im.ReceivedFromSection) AS ReceivedFromSection,
        STRING_AGG(im.InvoiceNo, ', ') AS InvoiceNos,
        STRING_AGG(im.VendorName, ', ') AS Vendors,
        SUM(ISNULL(im.TotalAmount,0)) AS TotalAmount,
        SUM(ISNULL(im.NetPayable,0)) AS TotalNet
    FROM bill_invoice_map bim
    JOIN invoice_master im ON im.Id = bim.InvoiceId
    GROUP BY bim.BillInitialId
),
ReplyAgg AS (
    SELECT
        csr.BillId,
        MAX(csr.ReplyDate) AS LastReplyDate,
        -- latest reply text (safe fallback)
        (SELECT TOP 1 ReplyText
         FROM concerned_section_reply x
         WHERE x.BillId = csr.BillId
         ORDER BY x.Id DESC) AS ReplyText
    FROM concerned_section_reply csr
    GROUP BY csr.BillId
)
SELECT
    lr.Id AS BillEntryId,
    lr.BillInitialId,
    lr.TokenNo,
    lr.AllotedDate,
    lr.Remarks,
    lr.reviewed,
    lr.concerned_reply,

    bi.BillNumber,
    bi.BillReceivedDate,

    e.EmployeeName AS AllotedName,

    ia.InvoiceCount,
    ia.InvoiceNos,
    ia.Vendors,
    ia.SectionDAName,
    ia.ReceivedFromSection,
    ia.TotalAmount,
    ia.TotalNet,

    ra.ReplyText,
    ra.LastReplyDate
FROM LatestReturn lr
JOIN bill_initial_entry bi ON bi.Id = lr.BillInitialId
LEFT JOIN employee_master e ON e.Id = lr.AllotedDealingAsst
LEFT JOIN InvoiceAgg ia ON ia.BillInitialId = lr.BillInitialId
LEFT JOIN ReplyAgg ra ON ra.BillId = lr.Id
WHERE lr.rn = 1
ORDER BY lr.Id DESC
";

$rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/*
  Set your COMMON DETAILS PAGE here:
  1) bill_history.php?id=BillInitialId   (recommended for viewing status history)
  OR
  2) process_bill.php (requires POST bill_id, so not good for "View" link)
*/
$commonViewPage = "bill_history.php?id=";  // change if your common page name differs
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Returned Bills</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .page-content{margin-left:240px;padding:90px 20px 20px;}
        .card-shadow{box-shadow:0 0.5rem 1rem rgba(0,0,0,.15);}
        .small-muted{font-size:12px;color:#6c757d;}
        .nowrap{white-space:nowrap;}
    </style>
</head>
<body class="bg-light">

<?php include '../layout/topbar.php'; ?>
<?php include '../layout/sidebar.php'; ?>

<div class="page-content">
    <div class="card card-shadow p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-danger fw-bold m-0">📄 Returned Bills (Detailed)</h4>
            <span class="badge bg-dark">Total: <?= count($rows) ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="nowrap">BillEntry ID</th>
                        <th class="nowrap">Bill No</th>
                        <th class="nowrap">Token No</th>
                        <th class="nowrap">Received Date</th>

                        <th>From Section</th>
                        <th>Section DA</th>

                        <th>Alloted To</th>
                        <th class="nowrap">Alloted Date</th>

                        <th class="text-end">Invoice Count</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Total Net</th>

                        <th>Reason (Remarks)</th>
                        <th>Concerned Reply</th>

                        <th class="nowrap">Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if(count($rows) > 0): ?>
                    <?php foreach($rows as $r): ?>
                        <tr>
                            <td class="nowrap"><?= (int)$r['BillEntryId'] ?></td>

                            <td class="nowrap">
                                <div class="fw-bold"><?= h($r['BillNumber'] ?? '-') ?></div>
                                <div class="small-muted">BillInitialId: <?= (int)$r['BillInitialId'] ?></div>
                            </td>

                            <td class="nowrap"><?= h($r['TokenNo'] ?? '-') ?></td>

                            <td class="nowrap">
                                <?= !empty($r['BillReceivedDate']) ? date('d-m-Y', strtotime($r['BillReceivedDate'])) : '-' ?>
                            </td>

                            <td><?= h($r['ReceivedFromSection'] ?? '-') ?></td>
                            <td><?= h($r['SectionDAName'] ?? '-') ?></td>

                            <td><?= h($r['AllotedName'] ?? '-') ?></td>

                            <td class="nowrap">
                                <?= !empty($r['AllotedDate']) ? date('d-m-Y', strtotime($r['AllotedDate'])) : '-' ?>
                            </td>

                            <td class="text-end"><?= (int)($r['InvoiceCount'] ?? 0) ?></td>
                            <td class="text-end"><?= nf($r['TotalAmount'] ?? 0) ?></td>
                            <td class="text-end fw-bold text-success"><?= nf($r['TotalNet'] ?? 0) ?></td>

                            <td style="min-width:200px;"><?= nl2br(h($r['Remarks'] ?? '-')) ?></td>

                            <td style="min-width:220px;">
                                <div><?= nl2br(h($r['ReplyText'] ?? '-')) ?></div>
                                <?php if(!empty($r['LastReplyDate'])): ?>
                                    <div class="small-muted mt-1">Reply Date: <?= date('d-m-Y H:i', strtotime($r['LastReplyDate'])) ?></div>
                                <?php endif; ?>
                            </td>

                            <td class="nowrap">
                                <!-- Common details page -->
                                <a href="<?= $commonViewPage . (int)$r['BillInitialId'] ?>"
                                   class="btn btn-sm btn-outline-dark mb-1">
                                    View Details
                                </a>
                                <br>

                                <?php if(($r['reviewed'] ?? 'N') == 'Y'): ?>
                                    <a href="returned_bill_resubmit.php?id=<?= (int)$r['BillEntryId'] ?>"
                                       class="btn btn-sm btn-primary">
                                        Resubmit
                                    </a>
                                <?php elseif(($r['concerned_reply'] ?? 'N') == 'Y'): ?>
                                    <a href="returned_bill_preview.php?id=<?= (int)$r['BillEntryId'] ?>"
                                       class="btn btn-sm btn-warning">
                                        Preview & Review
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        Awaiting Reply
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="14" class="text-center text-danger fw-bold">No returned bills found</td>
                    </tr>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

        <?php if(count($rows) > 0): ?>
            <div class="small-muted mt-2">
                Note: "View Details" opens your common bill status page using BillInitialId.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

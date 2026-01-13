<?php
require_once('../tcpdf/tcpdf.php');
include '../config/db.php';

/* =========================
   SQL – BILL WISE DATA
========================= */
$sql = "
SELECT
    bi.Id AS BillId,
    bi.BillNumber,
    bi.BillReceivedDate,

    po.POOrderNo,
    po.POOrderDate,

    so.SanctionOrderNo,
    so.SanctionDate,

    im.InvoiceNo,
    im.InvoiceDate,
    im.VendorName,
    im.Amount AS GrossAmount,
    im.GSTAmount,
    im.ITAmount,
    im.TotalAmount AS NetAmount,

    im.BankName,
    im.IFSC,
    im.AccountNumber,
    im.PanNumber

FROM bill_initial_entry bi
JOIN bill_invoice_map bim ON bim.BillInitialId = bi.Id
JOIN invoice_master im ON im.Id = bim.InvoiceId
LEFT JOIN po_master po ON po.Id = im.POId
LEFT JOIN sanction_order_master so ON so.Id = im.SanctionId

ORDER BY bi.BillNumber, im.InvoiceNo
";

$rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TCPDF INIT
========================= */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('JIPMER');
$pdf->SetTitle('Bill Vouched Claim Cum Bill');
$pdf->SetMargins(10, 15, 10);
$pdf->SetAutoPageBreak(true, 15);

/* =========================
   VARIABLES
========================= */
$currentBill = '';
$sl = 1;

$totalGross = 0;
$totalTDS   = 0;
$totalGST   = 0;
$totalIT    = 0;
$totalNet   = 0;

/* =========================
   LOOP – BILL WISE
========================= */
foreach ($rows as $r) {

    /* ===== NEW BILL ===== */
    if ($currentBill != $r['BillId']) {

        /* Close previous bill table */
        if ($currentBill != '') {
            $pdf->writeHTML("
            <tr bgcolor='#f2f2f2'>
                <td colspan='3' align='right'><b>Total</b></td>
                <td align='right'><b>{$totalGross}</b></td>
                <td align='right'><b>{$totalTDS}</b></td>
                <td align='right'><b>{$totalGST}</b></td>
                <td align='right'><b>{$totalIT}</b></td>
                <td align='right'><b>{$totalNet}</b></td>
            </tr>
            </table>
            ");
        }

        /* New page for each bill */
        $pdf->AddPage();

        /* Header */
        $pdf->writeHTML("
        <table width='100%'>
            <tr>
                <td width='15%'><img src='../images/emblem.png' height='40'></td>
                <td width='70%' align='center'>
                    <b>JAWAHARLAL INSTITUTE OF POSTGRADUATE MEDICAL EDUCATION & RESEARCH</b><br>
                    <b>PUDUCHERRY – 605006</b><br><br>
                    <b>BILL VOUCHED CLAIM CUM BILL</b><br>
                    <small>(For Bill Passing Use Only)</small>
                </td>
                <td width='15%' align='right'><img src='../images/logo.png' height='40'></td>
            </tr>
        </table>
        <hr>
        ");

        /* Bill info */
        $billNo   = $r['BillNumber'];
        $billDate = date('d-m-Y', strtotime($r['BillReceivedDate']));

        $pdf->writeHTML("
        <table width='100%' border='1' cellpadding='4'>
            <tr>
                <td width='70%'><b>Bill No:</b> {$billNo}</td>
                <td width='30%'><b>Date:</b> {$billDate}</td>
            </tr>
        </table><br>
        ");

        /* Table header */
        $pdf->writeHTML("
        <table border='1' cellpadding='4' width='100%'>
        <tr bgcolor='#e6e6e6'>
            <th width='5%'>Sl</th>
            <th width='30%'>Description of Charge / Vendor</th>
            <th width='25%'>Bank Details</th>
            <th width='10%'>Gross</th>
            <th width='8%'>2% TDS</th>
            <th width='8%'>GST</th>
            <th width='8%'>IT</th>
            <th width='10%'>Net</th>
        </tr>
        ");

        /* Reset counters */
        $sl = 1;
        $totalGross = $totalTDS = $totalGST = $totalIT = $totalNet = 0;

        $currentBill = $r['BillId'];
    }

    /* ===== ROW DATA ===== */
    $gross = $r['GrossAmount'];
    $tds   = round($gross * 0.02, 2);
    $gst   = $r['GSTAmount'];
    $it    = $r['ITAmount'];
    $net   = $r['NetAmount'];

    $bank = "
        A/C: {$r['AccountNumber']}<br>
        Bank: {$r['BankName']}<br>
        IFSC: {$r['IFSC']}<br>
        PAN: {$r['PanNumber']}
    ";

    $pdf->writeHTML("
    <tr>
        <td>{$sl}</td>
        <td>
            <b>{$r['VendorName']}</b><br>
            PO No: {$r['POOrderNo']}<br>
            Sanction No: {$r['SanctionOrderNo']}
        </td>
        <td>{$bank}</td>
        <td align='right'>{$gross}</td>
        <td align='right'>{$tds}</td>
        <td align='right'>{$gst}</td>
        <td align='right'>{$it}</td>
        <td align='right'>{$net}</td>
    </tr>
    ");

    /* Totals */
    $totalGross += $gross;
    $totalTDS   += $tds;
    $totalGST   += $gst;
    $totalIT    += $it;
    $totalNet   += $net;

    $sl++;
}

/* ===== CLOSE LAST BILL ===== */
$pdf->writeHTML("
<tr bgcolor='#f2f2f2'>
    <td colspan='3' align='right'><b>Total</b></td>
    <td align='right'><b>{$totalGross}</b></td>
    <td align='right'><b>{$totalTDS}</b></td>
    <td align='right'><b>{$totalGST}</b></td>
    <td align='right'><b>{$totalIT}</b></td>
    <td align='right'><b>{$totalNet}</b></td>
</tr>
</table>
");

/* =========================
   OUTPUT
========================= */
$pdf->Output('bill_claim_report.pdf', 'I');

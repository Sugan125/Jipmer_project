<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../tcpdf/tcpdf.php');
include '../config/db.php';

if (!isset($_POST['bill_id'])) {
    die('Bill ID missing');
}

$billId = $_POST['bill_id'];

/* ================== FETCH BILL HEADER ================== */
$bill = $conn->prepare("
SELECT 
    bi.BillNumber,
    bi.BillReceivedDate,
    be.TokenNo
FROM bill_initial_entry bi
LEFT JOIN bill_entry be ON be.BillInitialId = bi.Id
WHERE bi.Id = :id
");
$bill->execute(['id' => $billId]);
$bill = $bill->fetch(PDO::FETCH_ASSOC);

function amountInWords($number) {
    $no = floor($number);
    $decimal = round(($number - $no) * 100);

    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
        17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
        70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );

    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');

    // Pad number to 9 digits
    $number = str_pad($no, 9, "0", STR_PAD_LEFT);
    $crore   = intval(substr($number, 0, 2));
    $lakh    = intval(substr($number, 2, 2));
    $thousand= intval(substr($number, 4, 2));
    $hundred = intval(substr($number, 6, 1));
    $tenUnit = intval(substr($number, 7, 2));

    $wordsArr = array();

    if ($crore > 0) {
        if ($crore < 21) $wordsArr[] = $words[$crore] . " Crore";
        else $wordsArr[] = $words[(int)($crore/10)*10] . " " . $words[$crore%10] . " Crore";
    }

    if ($lakh > 0) {
        if ($lakh < 21) $wordsArr[] = $words[$lakh] . " Lakh";
        else $wordsArr[] = $words[(int)($lakh/10)*10] . " " . $words[$lakh%10] . " Lakh";
    }

    if ($thousand > 0) {
        if ($thousand < 21) $wordsArr[] = $words[$thousand] . " Thousand";
        else $wordsArr[] = $words[(int)($thousand/10)*10] . " " . $words[$thousand%10] . " Thousand";
    }

    if ($hundred > 0) {
        $wordsArr[] = $words[$hundred] . " Hundred";
    }

    if ($tenUnit > 0) {
        if ($tenUnit < 21) $wordsArr[] = $words[$tenUnit];
        else $wordsArr[] = $words[(int)($tenUnit/10)*10] . " " . $words[$tenUnit%10];
    }

    $str = implode(" ", array_filter($wordsArr));

    if ($decimal > 0) {
        $str .= " and " . $decimal . "/100";
    }

    return "Rupees " . $str . " only";
}

/* ================== FETCH INVOICES ================== */
$stmt = $conn->prepare("
SELECT 
    im.VendorName,
    im.InvoiceNo,
    im.InvoiceDate,

    -- Gross = invoice amount
    COALESCE(im.Amount,0) AS Gross,

    -- Deductions
    COALESCE(im.TDSGSTAmount,0) AS TDSGSTAmount,
    COALESCE(im.TDSITAmount,0)  AS TDSITAmount,
    (COALESCE(im.TDSGSTAmount,0) + COALESCE(im.TDSITAmount,0)) AS TotalTDS,

    -- Amount after deductions
    COALESCE(im.NetPayable,0) AS Net,

    im.BankName,
    im.AccountNumber,
    im.IFSC,
    im.PanNumber,

    dm.DeptName,

    ho.DetailsHeadCode,
    ho.DetailsHeadName,
    ho.ObjectHeadCode,
    ho.SubDetailsHeadName

FROM bill_invoice_map bim
JOIN invoice_master im ON im.Id = bim.InvoiceId

LEFT JOIN hoa_master ho ON ho.HoaId = im.HOAId   -- IMPORTANT (HoaId)
LEFT JOIN dept_master dm ON dm.Id = im.DeptId

WHERE bim.BillInitialId = :billId
ORDER BY im.InvoiceDate ASC
");
$stmt->execute(['billId' => $billId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$token_no = $bill['TokenNo'] ?? '';

// Bill No & Date (from bill_initial_entry)
$bill_no   = $bill['BillNumber'] ?? '';
$bill_date = !empty($bill['BillReceivedDate'])
    ? date('d-m-Y', strtotime($bill['BillReceivedDate']))
    : '';

// Head of Account (from first invoice row)
$hoaCode = $rows[0]['DetailsHeadCode'] ?? '';
$hoaName = $rows[0]['DetailsHeadName'] ?? '';

// Object Head / Heading
$objectCode = $rows[0]['ObjectHeadCode'] ?? '';
$heading    = $rows[0]['SubDetailsHeadName'] ?? '';

// GG No (derive from Bill No)
$gg_no = str_replace('GG-', '', $bill_no);

// PFMS Sanction (static or from table if exists)
$pfms_details = $pfms_details ?? '';

if (!$rows) {
    die('No invoices found');
}

/* ================== TCPDF INIT ================== */
$pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica','',9);
/* ================== LOGOS WITH TAMIL TEXT BETWEEN ================== */

// Logo paths
$leftLogo = 'D:/JIPMER_Project/images/enblum.png';
$rightLogo = 'D:/JIPMER_Project/images/logo.png';
$logoHeight = 25; // height of logos

$pageWidth = $pdf->getPageWidth();
$margins = $pdf->getMargins();

// Insert left logo
$pdf->Image($leftLogo, $margins['left'], 10, 0, $logoHeight);

// Insert right logo
$pdf->Image($rightLogo, $pageWidth - $margins['right'] - $logoHeight, 10, 0, $logoHeight);

// Calculate available width between logos
$leftX = $margins['left'] + $logoHeight + 2;  // 2mm padding after left logo
$rightX = $pageWidth - $margins['right'] - $logoHeight - 2;
$textWidth = $rightX - $leftX;

// Add Tamil font
$fontfile = 'D:/JIPMER_Project/fonts/NotoSansTamil-Regular.ttf';
$fontname = TCPDF_FONTS::addTTFfont($fontfile, 'TrueTypeUnicode', '', 32);
$pdf->SetFont($fontname, '', 10);

// Set starting position for text (vertically centered with logos)
$startY = 10 + 2; // 10 = top of logos, +2 mm padding
$pdf->SetXY($leftX, $startY);

// Tamil text
$tamilText = "ஜவஹர்லால் முதுகலை மருத்துவக் கல்வி மற்றும் ஆராய்ச்சி நிறுவனம் (JIPMER), இந்தியாவின் பாண்டிச்சேரியில் அமைந்துள்ள ஒரு மருத்துவப் பள்ளியாகும்.";

$pdf->MultiCell(
    $textWidth,
    6,
    $tamilText,
    0,
    'C',  // center between logos
    false,
    1,
    '',
    '',
    true
);

// English institute name below Tamil
$pdf->SetFont('helvetica','B',10);
$pdf->SetX($leftX);
$pdf->MultiCell(
    $textWidth,
    6,
    "JAWAHARLAL INSTITUTE OF POSTGRADUATE MEDICAL EDUCATION & RESEARCH, PUDUCHERRY - 605006",
    0,
    'C',
    false,
    1,
    '',
    '',
    true
);

// Move cursor below logos and text


$pdf->Ln(2);
$pdf->Cell(0,0,'','T');
$pdf->Ln(4);

// Fully Vouched Claim Cum Bill
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0,6,'FULLY VOUCHED CLAIM CUM BILL',0,1,'C');

$pdf->SetFont('helvetica','',8);
$pdf->Cell(0,5,'[See rules 21,22,23 of Subsidiary Instructions]',0,1,'C');

$pdf->Ln(2);
$pdf->Cell(0,0,'','T');
$pdf->Ln(4);


/* ================== BILL INFO ================== */
$pdf->SetFont('helvetica','B',9);
$pdf->Cell(120,6,'Detailed Bill',0,0,'L');

$pdf->Cell(70,6,'For Bill Passing Use Only',1,1,'C');

$pdf->SetFont('helvetica','',9);
$pdf->Cell(120,6,'',0,0);
$pdf->Cell(35,6,'Token No',1,0);
$pdf->Cell(35,6,$token_no,1,1);

$pdf->Cell(120,6,'',0,0);
$pdf->Cell(35,6,'Date',1,0);
$pdf->Cell(35,6,date('d/m/Y'),1,1);

/* ================== HEAD OF ACCOUNT ================== */
$pdf->Cell(63,7,'HEAD OF ACCOUNT: '.$hoaCode.' - '.$hoaName,1,0);
$pdf->Cell(63,7,'CLAUSE: Class III - G & S',1,0);
$pdf->Cell(32,7,'GG-'.$gg_no,1,0);
$pdf->Cell(32,7,'Dated: '.$bill_date,1,1);

$pdf->Cell(
    0,7,
    'HEADING: '.$heading,
    1,1
);

$pdf->MultiCell(
    0,7,
    'PFMS SANCTION: [539741545]G-20018/5/2025-INI-II | 850000000.00 |S56755656 | 5465456456456',
    1
);
/* ================== TABLE HEADER ================== */
$table = '
<table border="1" cellpadding="3" cellspacing="0" width="100%">
<tr align="center" style="font-weight:bold;">
    <th width="4%">No</th>
    <th width="33%">Description of charge and number and date of authority</th>
    <th width="25%">Bank Details of Vendor</th>
    <th width="9%">Gross</th>
    <th width="8%">2% TDS GST</th>
    <th width="7%">2% or 10% IT</th>
    <th width="14%">Amount after Deductions</th>
</tr>
';

$no = 1;
$totalGross = 0;
$totalTDSGST = 0;
$totalTDSIT = 0;
$totalNet = 0;


foreach ($rows as $r) {

    $table .= '
    <tr>
        <td align="center">'.$no.'</td>

        <td>
            <b>VENDOR:</b> '.$r['VendorName'].'<br>
            <b>DEPT:</b> '.$r['DeptName'].'<br>
            <b>INV NO:</b> '.$r['InvoiceNo'].'<br>
            <b>INV DATE:</b> '.date('d-m-Y',strtotime($r['InvoiceDate'])).'
        </td>

        <td>
            A/C: '.$r['AccountNumber'].'<br>
            BANK: '.$r['BankName'].'<br>
            IFSC: '.$r['IFSC'].'<br>
            PAN: '.$r['PanNumber'].'
        </td>

       <td align="right">'.number_format($r['Gross'],2).'</td>
<td align="right">'.number_format($r['TDSGSTAmount'],2).'</td>
<td align="right">'.number_format($r['TDSITAmount'],2).'</td>
<td align="right">'.number_format($r['Net'],2).'</td>

    </tr>';
$totalGross  += (float)$r['Gross'];
$totalTDSGST += (float)$r['TDSGSTAmount'];
$totalTDSIT  += (float)$r['TDSITAmount'];
$totalNet    += (float)$r['Net'];


    $no++;
}
/* ===== EMPTY ROWS ===== */
for ($i = 0; $i < 3; $i++) {
    $table .= '<tr>
        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
        <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
    </tr>';
}

$table .= '
<tr>
    <td colspan="7" style="font-weight:bold;">
        Expenditure towards 22nd meeting of standing Finance Committee of JIPMER
        to be held on 10 December 2025 at New Delhi
    </td>
</tr>';

/* ===== TOTAL ROW ===== */
$table .= '
<tr style="font-weight:bold;">
    <td colspan="3" align="right">TOTAL</td>
    <td align="right">'.number_format($totalGross,2).'</td>
   <td align="right">'.number_format($totalTDSGST,2).'</td>
<td align="right">'.number_format($totalTDSIT,2).'</td>
<td align="right">'.number_format($totalNet,2).'</td>
</tr>';

/* ===== AMOUNT IN WORDS ===== */
$table .= '
<tr>
    <td colspan="7" align="center">
        ('.amountInWords(round($totalNet)).')
    </td>
</tr>
</table>';

$pdf->writeHTML($table,true,false,true,false,'');


/* ================== OUTPUT ================== */
$pdf->Output('Bill_Vouched_Claim_Cum_Bill.pdf', 'I');

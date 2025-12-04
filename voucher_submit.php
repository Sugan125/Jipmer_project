<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

include __DIR__ . '/db.php';

$user    = $_SESSION['username'];
$billId  = $_POST['billno'] ?? '';
$voucher = $_POST['voucher'] ?? '';
$ip      = $_SERVER['REMOTE_ADDR'];


// VALIDATION
if ($billId == "" || $voucher == "") {
    echo json_encode(["status" => "error", "message" => "Missing Inputs"]);
    exit();
}

try {

    // 1️⃣ CHECK BILL DUPLICATE (FETCH BOTH FIELDS)
  $checkBill = $conn->prepare("SELECT BillNumber,VoucherNumber FROM voucher WHERE BillNumber = ?");
$checkBill->execute([$billId]);
$existing = $checkBill->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Found a row
    echo json_encode([
        "status"  => "bill_exists",
        "message" => "Voucher entry already done for this bill.",
        "voucher" => $existing['VoucherNumber']
    ]);
    exit();
}

    // 2️⃣ CHECK VOUCHER DUPLICATE
    $checkVoucher = $conn->prepare("SELECT VoucherNumber FROM voucher WHERE VoucherNumber = ?");
    $checkVoucher->execute([$voucher]);
$existingVoucher = $checkVoucher->fetch(PDO::FETCH_ASSOC);

if ($existingVoucher) {
    echo json_encode([
        "status"  => "voucher_exists",
        "message" => "This voucher number already exists."
    ]);
    exit();
}

    // 3️⃣ INSERT NEW ENTRY
    $stmt = $conn->prepare("
        INSERT INTO voucher (BillNumber, VoucherNumber, CreatedBy, CreatedIP)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$billId, $voucher, $user, $ip]);

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

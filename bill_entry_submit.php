<?php
include 'db.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['username'])) exit("Not logged in");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $billno  = $_POST['billno'];
    $major   = $_POST['major'] ?? '';
    $submaj  = $_POST['submajor'] ?? '';
    $minor   = $_POST['minor'] ?? '';
    $submin  = $_POST['subminor'] ?? '';
    $detail  = $_POST['detail'] ?? '';
    $object  = $_POST['object'] ?? '';
    $alloted = $_POST['alloted'];
    $status  = $_POST['status'];
    $remarks = $_POST['remarks'] ?? '';
    $ip      = $_SERVER['REMOTE_ADDR'];
    $user    = $_SESSION['username'];

    $fullHOA = implode(' - ', array_filter([$major,$submaj,$minor,$submin,$detail,$object]));

    $stmt = $conn->prepare("INSERT INTO bill_entry
        (BillNo, MajorHead, SubMajorHead, MinorHead, SubMinorHead, DetailHead, ObjectHead, FullHOA, AllotedTo, Status, Remarks, CreatedIP, CreatedBy)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([$billno,$major,$submaj,$minor,$submin,$detail,$object,$fullHOA,$alloted,$status,$remarks,$ip,$user]);

    echo "success";
}

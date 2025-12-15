<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

$id        = intval($_POST['id'] ?? 0);
$finyear   = trim($_POST['finyear'] ?? '');
$isCurrent = isset($_POST['iscurrent']) ? 1 : 0;

if ($id <= 0 || $finyear === '') {
    die('Invalid data');
}

try {

    $conn->beginTransaction();

    // If this year is set as current, unset all others
    if ($isCurrent) {
        $conn->prepare("
            UPDATE fin_year_master
            SET IsCurrent = 0
        ")->execute();
    }

    // Update selected financial year
    $stmt = $conn->prepare("
        UPDATE fin_year_master
        SET FinYear = ?, IsCurrent = ?
        WHERE Id = ?
    ");
    $stmt->execute([$finyear, $isCurrent, $id]);

    $conn->commit();

    header("Location: fin_year_master.php");
    exit;

} catch (Exception $e) {

    $conn->rollBack();
    die("Error: " . $e->getMessage());
}

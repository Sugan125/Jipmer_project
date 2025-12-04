<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("db.php");

try {
    $stmt = $conn->query("SELECT TOP 1 * FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
}

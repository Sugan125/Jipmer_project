<?php
// PDO SQLSRV connection
$serverName = "localhost"; // your SQL Server
$database   = "jipmer_project";
$dbUser     = "sa";
$password   = "Ucpsm123";
if (!defined('BASE_URL')) {
    define('BASE_URL', '/JIPMER_Project');
}

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $dbUser, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e){
    die("DB Connection Failed: " . $e->getMessage());
}
?>

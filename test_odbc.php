<?php
session_start();

// ODBC Connection parameters
$user   = "sa";               // SQL Server username
$pass   = "Ucpsm123";     // SQL Server password
$server = "localhost"; // SQL Server instance
$db     = "jipmer_project";   // Database name

$connstr = "DRIVER={ODBC Driver 17 for SQL Server};SERVER=$server;DATABASE=$db;";

$conn = odbc_connect($connstr, $user, $pass);

if ($conn) {
    echo "Connection established via ODBC!<br>";
} else {
    echo "Connection not established<br>";
    echo odbc_errormsg();
}

// Track example
//$track = "U:" . $_SESSION['userName'] . ",Date:" . date("Y-m-d H:i") . ",IP:" . $_SERVER['REMOTE_ADDR'];
?>

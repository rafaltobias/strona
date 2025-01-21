<?php
$serverName = "DESKTOP-IMLU88C\MSSQLSERVER01";
$connectionOptions = [
    "Database" => "skleprtv",
    "Uid" => "st931",
    "PWD" => "st931"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
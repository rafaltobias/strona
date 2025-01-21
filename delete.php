<?php
require 'config.php';
session_start(); 

if ($_SESSION['user_role'] != 1) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

$sql_check = "SELECT ID_Uprawnienia FROM Uzytkownik WHERE ID_Uzytkownika = ?";
$stmt_check = sqlsrv_query($conn, $sql_check, [$id]);

if ($stmt_check === false) {
    die(print_r(sqlsrv_errors(), true));
}

$user = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

if ($user['ID_Uprawnienia'] == 1) {
    echo "<script>alert('Nie możesz usunąć administratora.'); window.location.href='panel.php';</script>";
    exit;
}

$sql = "DELETE FROM Uzytkownik WHERE ID_Uzytkownika = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

header("Location: panel.php");
exit;
?>
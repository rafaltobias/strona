<?php
session_start();
include 'db.php';

// Pobranie tabeli z parametru GET lub POST
$table = isset($_GET['table']) ? $_GET['table'] : '';
$id_field = "ID_" . ucfirst($table); // Automatyczne określenie pola ID (np. "ID_Produkt")

// Obsługa dodawania (Create)
if (isset($_POST['add'])) {
    switch ($table) {
        case 'Produkt':
            $sql = "INSERT INTO Produkt (Nazwa_Produktu, Cena, Stan_Magazynowy, Kategoria) VALUES (?, ?, ?, ?)";
            $params = [
                $_POST['Nazwa_Produktu'],
                $_POST['Cena'],
                $_POST['Stan_Magazynowy'],
                $_POST['Kategoria']
            ];
            break;

        case 'Koszyk':
            $sql = "INSERT INTO Koszyk (ID_Uzytkownika, Data_Utworzenia) VALUES (?, ?)";
            $params = [
                $_POST['ID_Uzytkownika'],
                $_POST['Data_Utworzenia']
            ];
            break;

        // Dodaj kolejne przypadki dla innych tabel
        default:
            die("Nieobsługiwana tabela!");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    header("Location: admin.php?table=$table");
    exit();
}

// Obsługa usuwania (Delete)
if (isset($_GET['delete'])) {
    $id_value = $_GET['delete'];

    $sql = "DELETE FROM $table WHERE $id_field = ?";
    $params = [$id_value];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    header("Location: admin.php?table=$table");
    exit();
}

// Obsługa edycji (Update)
if (isset($_POST['update'])) {
    switch ($table) {
        case 'Produkt':
            $sql = "UPDATE Produkt SET Nazwa_Produktu = ?, Cena = ?, Stan_Magazynowy = ?, Kategoria = ? WHERE ID_Produktu = ?";
            $params = [
                $_POST['Nazwa_Produktu'],
                $_POST['Cena'],
                $_POST['Stan_Magazynowy'],
                $_POST['Kategoria'],
                $_POST['ID_Produktu']
            ];
            break;

        case 'Koszyk':
            $sql = "UPDATE Koszyk SET ID_Uzytkownika = ?, Data_Utworzenia = ? WHERE ID_Koszyka = ?";
            $params = [
                $_POST['ID_Uzytkownika'],
                $_POST['Data_Utworzenia'],
                $_POST['ID_Koszyka']
            ];
            break;

        // Dodaj kolejne przypadki dla innych tabel
        default:
            die("Nieobsługiwana tabela!");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    header("Location: admin.php?table=$table");
    exit();
}

// W przypadku błędu
die("Nieprawidłowa operacja!");

?>
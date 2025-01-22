<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    die("Musisz być zalogowany, aby zobaczyć szczegóły zamówienia.");
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Pobranie ID zamówienia z parametru GET
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    die("Nieprawidłowe ID zamówienia.");
}

// Zapytanie do bazy danych, aby pobrać szczegóły zamówienia
$query = "SELECT z.ID_Zamowienia, z.Data_Zlozenia, z.Status, z.Laczna_Kwota, p.Nazwa_Produktu, zp.Ilosc, zp.Cena
          FROM Zamowienie z
          JOIN Zamowienie_Produkt zp ON z.ID_Zamowienia = zp.ID_Zamowienia
          JOIN Produkt p ON zp.ID_Produktu = p.ID_Produktu
          WHERE z.ID_Zamowienia = ? AND z.ID_Uzytkownika = ?";

$params = [$order_id, $id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);

// Pobranie danych zamówienia
$order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$order) {
    die("Nie znaleziono zamówienia.");
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły Zamówienia</title>
</head>
<body>
    <h1>Szczegóły zamówienia #<?= htmlspecialchars($order['ID_Zamowienia']) ?></h1>

    <p><strong>Data złożenia:</strong> <?= $order['Data_Zlozenia']->format('Y-m-d H:i:s') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['Status']) ?></p>
    <p><strong>Łączna kwota:</strong> <?= number_format($order['Laczna_Kwota'], 2) ?> zł</p>

    <h2>Produkty w zamówieniu</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Nazwa produktu</th>
                <th>Ilość</th>
                <th>Cena</th>
                <th>Łączna cena</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Pętla po produktach w zamówieniu
            do {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($order['Nazwa_Produktu']) . '</td>';
                echo '<td>' . $order['Ilosc'] . '</td>';
                echo '<td>' . number_format($order['Cena'], 2) . ' zł</td>';
                echo '<td>' . number_format($order['Cena'] * $order['Ilosc'], 2) . ' zł</td>';
                echo '</tr>';
            } while ($order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
            ?>
        </tbody>
    </table>

    <a href="zamowienia.php">Powrót do zamówień</a>
</body>
</html>

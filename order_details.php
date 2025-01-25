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

// Przypisanie danych zamówienia do zmiennych
$order_id = $order['ID_Zamowienia'];
$order_date = $order['Data_Zlozenia']->format('Y-m-d H:i:s');
$order_status = $order['Status'];
$order_total = number_format($order['Laczna_Kwota'], 2);
$products = [];

do {
    $products[] = [
        'name' => $order['Nazwa_Produktu'],
        'quantity' => $order['Ilosc'],
        'price' => number_format($order['Cena'], 2),
        'total' => number_format($order['Cena'] * $order['Ilosc'], 2),
    ];
} while ($order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));

// Funkcja anulowania zamówienia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order']) && $order_status !== 'Zrealizowane' && $order_status !== 'Wysłane') {
    // Anulowanie zamówienia
    $cancel_query = "UPDATE Zamowienie SET Status = 'Anulowane' WHERE ID_Zamowienia = ? AND ID_Uzytkownika = ?";
    $cancel_params = [$order_id, $id_uzytkownika];
    $cancel_stmt = sqlsrv_prepare($conn, $cancel_query, $cancel_params);

    if ($cancel_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($cancel_stmt);

    // Po anulowaniu, przekierowanie do strony zamówień
    header("Location: zamowienia.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły Zamówienia</title>
    <link rel="stylesheet" href="css/details.css">
</head>
<body>
    <?php include "header.php"; ?>
    <h1>Szczegóły zamówienia #<?= htmlspecialchars($order_id) ?></h1>

    <div class="order-details">
        <p><strong>Data złożenia:</strong> <?= htmlspecialchars($order_date) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order_status) ?></p>
        <p><strong>Łączna kwota:</strong> <?= htmlspecialchars($order_total) ?> zł</p>
    </div>

    <h2>Produkty w zamówieniu</h2>
    <table>
        <thead>
            <tr>
                <th>Nazwa produktu</th>
                <th>Ilość</th>
                <th>Cena</th>
                <th>Łączna cena</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['quantity']) ?></td>
                    <td><?= htmlspecialchars($product['price']) ?> zł</td>
                    <td><?= htmlspecialchars($product['total']) ?> zł</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Sprawdzanie, czy zamówienie może zostać anulowane -->
    <?php if ($order_status !== 'Zrealizowane' && $order_status !== 'Wysłane'): ?>
        <form method="POST">
            <button type="submit" name="cancel_order" class="button cancel-button">Anuluj zamówienie</button>
        </form>
    <?php else: ?>
        <p><strong>Nie możesz anulować tego zamówienia, ponieważ zostało już zrealizowane lub wysłane.</strong></p>
    <?php endif; ?>

    <a href="zamowienia.php" class="button">Powrót do zamówień</a>
</body>
</html>

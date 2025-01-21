<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    die("Musisz być zalogowany, aby finalizować zamówienie.");
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Znalezienie najnowszego koszyka dla użytkownika
$query = "SELECT TOP 1 * FROM Koszyk WHERE ID_Uzytkownika = ? ORDER BY Data_Utworzenia DESC";
$params = [$id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$koszyk = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Jeśli nie znaleziono koszyka
if (!$koszyk) {
    die("Nie masz aktywnego koszyka.");
}

// Pobranie ID koszyka
$koszyk_id = $koszyk['ID_Koszyka'];

// Pobranie produktów w koszyku
$query_products = "SELECT p.ID_Produktu, p.Nazwa_Produktu, p.Cena, kp.Ilosc
                   FROM Koszyk_Produkt kp
                   JOIN Produkt p ON p.ID_Produktu = kp.ID_Produktu
                   WHERE kp.ID_Koszyka = ?";
$stmt_products = sqlsrv_prepare($conn, $query_products, [$koszyk_id]);
if ($stmt_products === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt_products);
$products = [];
$total_amount = 0;

// Pobranie danych produktów
while ($row = sqlsrv_fetch_array($stmt_products, SQLSRV_FETCH_ASSOC)) {
    $products[] = $row;
    $total_amount += $row['Cena'] * $row['Ilosc'];
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizacja Zamówienia</title>
</head>
<body>
    <h1>Finalizacja Zamówienia</h1>
    
    <h2>Produkty w zamówieniu</h2>
    <?php if (count($products) > 0): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Nazwa Produktu</th>
                    <th>Cena</th>
                    <th>Ilość</th>
                    <th>Łączna cena</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Nazwa_Produktu']) ?></td>
                        <td><?= number_format($row['Cena'], 2) ?> zł</td>
                        <td><?= $row['Ilosc'] ?></td>
                        <td><?= number_format($row['Cena'] * $row['Ilosc'], 2) ?> zł</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Łączna kwota: <?= number_format($total_amount, 2) ?> zł</strong></p>
        
        <!-- Formularz do finalizacji -->
        <form action="confirm_order.php" method="POST">
            <h3>Adres wysyłki</h3>
            <label for="address">Adres:</label>
            <input type="text" name="address" id="address" required><br>

            <h3>Metoda płatności</h3>
            <label for="payment_method">Wybierz metodę płatności:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="credit_card">Karta kredytowa</option>
                <option value="paypal">PayPal</option>
            </select><br><br>

            <button type="submit">Złóż zamówienie</button>
        </form>
    <?php else: ?>
        <p>Twój koszyk jest pusty.</p>
    <?php endif; ?>
</body>
</html>

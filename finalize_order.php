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
    <link rel="stylesheet" href="global.css">
    <style>/* Globalne ustawienia */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

header {
    background-color: #008000;
    color: white;
    padding: 20px;
    text-align: center;
}

header .logo {
    font-size: 28px;
    font-weight: bold;
}

header .user-menu a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
}

header .user-menu a:hover {
    text-decoration: underline;
}

/* Sekcja finalizacji zamówienia */
.finalize-section {
    padding: 40px;
    margin: 0 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.finalize-section h1,
.finalize-section h2,
.finalize-section h3 {
    color: #333;
}

.finalize-section table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
}

.finalize-section table th,
.finalize-section table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

.finalize-section table th {
    background-color: #f4f4f4;
}

.finalize-section table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.finalize-section .order-form {
    margin-top: 30px;
}

.finalize-section .order-form label {
    font-weight: bold;
    margin-top: 10px;
}

.finalize-section .order-form input,
.finalize-section .order-form select {
    width: 100%;
    padding: 8px;
    margin: 5px 0;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.finalize-section .order-form button {
    background-color: #008000;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.finalize-section .order-form button:hover {
    background-color: #006400;
}

/* Sekcja stopki */
footer {
    text-align: center;
    padding: 20px;
    background-color: #008000;
    color: white;
    position: fixed;
    width: 100%;
    bottom: 0;
}

footer p {
    margin: 0;
}
</style>
</head>
<body>
    <header>
        <div class="logo">TechHouse</div>
        <nav class="user-menu">
            <a href="index.php">Strona Główna</a>
            <a href="cart.php">Koszyk</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
    </header>

    <main>
        <section class="finalize-section">
            <h1>Finalizacja Zamówienia</h1>
            
            <h2>Produkty w zamówieniu</h2>
            <?php if (count($products) > 0): ?>
                <table class="product-table">
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
                <form action="confirm_order.php" method="POST" class="order-form">
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
        </section>
    </main>

    <footer>
        <p>&copy; 2025 TechHouse. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>

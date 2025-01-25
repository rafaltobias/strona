<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    die("Musisz być zalogowany, aby złożyć zamówienie.");
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Znalezienie najnowszego koszyka użytkownika
$query = "SELECT TOP 1 ID_Koszyka 
          FROM Koszyk 
          WHERE ID_Uzytkownika = ? 
          ORDER BY Data_Utworzenia DESC";

$params = [$id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$koszyk = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$koszyk) {
    die("Nie masz aktywnego koszyka.");
}

$id_koszyka = $koszyk['ID_Koszyka'];

// Zaczynamy transakcję
sqlsrv_begin_transaction($conn);

// Zbieranie danych o produktach w koszyku
$query = "SELECT kp.ID_Produktu, p.Cena, kp.Ilosc
          FROM Koszyk_Produkt kp
          JOIN Produkt p ON kp.ID_Produktu = p.ID_Produktu
          WHERE kp.ID_Koszyka = ?";

$params = [$id_koszyka];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);

$products = [];
$total_amount = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $products[] = $row;
    $total_amount += $row['Cena'] * $row['Ilosc'];
}

// Jeśli koszyk jest pusty
if (count($products) === 0) {
    echo "Twój koszyk jest pusty.";
    exit;
}

// Zapisanie zamówienia z OUTPUT INSERTED, aby pobrać ID nowego zamówienia
$order_query = "INSERT INTO Zamowienie (ID_Uzytkownika, Data_Zlozenia, Status, Laczna_Kwota)
                OUTPUT INSERTED.ID_Zamowienia
                VALUES (?, GETDATE(), 'Nowe', ?)";

$order_params = [$id_uzytkownika, $total_amount];
$order_stmt = sqlsrv_prepare($conn, $order_query, $order_params);

if ($order_stmt === false) {
    sqlsrv_rollback($conn);
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($order_stmt);

// Pobranie ID nowo utworzonego zamówienia
$order_id_row = sqlsrv_fetch_array($order_stmt, SQLSRV_FETCH_ASSOC);
$order_id = $order_id_row['ID_Zamowienia'];

// Przypisanie produktów do zamówienia oraz zmniejszenie stanu magazynowego
foreach ($products as $product) {
    // Dodanie produktu do zamówienia
    $insert_product_query = "INSERT INTO Zamowienie_Produkt (ID_Zamowienia, ID_Produktu, Ilosc, Cena)
                             VALUES (?, ?, ?, ?)";

    $insert_product_params = [$order_id, $product['ID_Produktu'], $product['Ilosc'], $product['Cena']];
    $insert_product_stmt = sqlsrv_prepare($conn, $insert_product_query, $insert_product_params);

    if ($insert_product_stmt === false) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($insert_product_stmt);

    // Zmniejszenie stanu magazynowego o zamówioną ilość
    $update_stock_query = "UPDATE Produkt
                           SET Stan_Magazynowy = Stan_Magazynowy - ?
                           WHERE ID_Produktu = ?";

    $update_stock_params = [$product['Ilosc'], $product['ID_Produktu']];
    $update_stock_stmt = sqlsrv_prepare($conn, $update_stock_query, $update_stock_params);

    if ($update_stock_stmt === false) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($update_stock_stmt);
}

// Po zapisaniu zamówienia, możemy usunąć produkty z koszyka (opcjonalnie)
$clear_cart_query = "DELETE FROM Koszyk_Produkt WHERE ID_Koszyka = ?";
$clear_cart_stmt = sqlsrv_prepare($conn, $clear_cart_query, [$id_koszyka]);

if ($clear_cart_stmt === false) {
    sqlsrv_rollback($conn);
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($clear_cart_stmt);

// Zatwierdzamy transakcję
sqlsrv_commit($conn);

?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potwierdzenie Zamówienia</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link do pliku CSS -->
</head>
<body>
    <header>
        <div class="logo">TechHouse</div>
        <nav class="user-menu">
            <a href="index.php">Strona Główna</a>
            <a href="cart.php">Koszyk</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
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

/* Sekcja potwierdzenia zamówienia */
.order-confirmation {
    padding: 40px;
    margin: 0 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.order-confirmation h1 {
    font-size: 28px;
    color: #333;
}

.order-confirmation p {
    font-size: 18px;
    color: #555;
    margin: 10px 0;
}

.order-confirmation .btn {
    display: inline-block;
    margin: 10px 15px;
    padding: 10px 20px;
    background-color: #008000;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 16px;
}

.order-confirmation .btn:hover {
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
    </header>

    <main>
        <section class="order-confirmation">
            <h1>Potwierdzenie Zamówienia</h1>
            <p>Twoje zamówienie zostało złożone pomyślnie!</p>
        
            <p><strong>Łączna Kwota:</strong> <?= number_format($total_amount, 2) ?> zł</p>

            <p>Chcesz kontynuować zakupy?</p>
            <a href="index.php" class="btn">Powróć do sklepu</a>
            <a href="cart.php" class="btn">Zobacz Koszyk</a>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 TechHouse. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>

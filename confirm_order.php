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

// Przypisanie produktów do zamówienia
foreach ($products as $product) {
    $insert_product_query = "INSERT INTO Zamowienie_Produkt (ID_Zamowienia, ID_Produktu, Ilosc, Cena)
                             VALUES (?, ?, ?, ?)";

    $insert_product_params = [$order_id, $product['ID_Produktu'], $product['Ilosc'], $product['Cena']];
    $insert_product_stmt = sqlsrv_prepare($conn, $insert_product_query, $insert_product_params);

    if ($insert_product_stmt === false) {
        sqlsrv_rollback($conn);
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($insert_product_stmt);
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

echo "Twoje zamówienie zostało złożone pomyślnie!";
echo "ID Zamówienia: " . $order_id;
?>

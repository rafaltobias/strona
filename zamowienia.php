<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    die("Musisz być zalogowany, aby zobaczyć swoje zamówienia.");
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Zapytanie do bazy danych, aby pobrać aktywne zamówienia dla użytkownika
$query = "SELECT z.ID_Zamowienia, z.Data_Zlozenia, z.Status, z.Laczna_Kwota 
          FROM Zamowienie z
          WHERE z.ID_Uzytkownika = ? AND z.Status IN ('Nowe', 'W trakcie', 'Oczekujące') 
          ORDER BY z.Data_Zlozenia DESC";

$params = [$id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);

$orders = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje Zamówienia</title>
    <link rel="stylesheet" href="css/zamowienia.css">
</head>
<body>
    <?php include "header.php";?>

    <main>
        <section class="orders-section">
            <h1>Twoje aktywne zamówienia</h1>

            <?php if (count($orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>ID Zamówienia</th>
                            <th>Data Złożenia</th>
                            <th>Status</th>
                            <th>Łączna Kwota</th>
                            <th>Opcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['ID_Zamowienia']) ?></td>
                                <td><?= $order['Data_Zlozenia']->format('Y-m-d H:i:s') ?></td>
                                <td><?= htmlspecialchars($order['Status']) ?></td>
                                <td><?= number_format($order['Laczna_Kwota'], 2) ?> zł</td>
                                <td>
                                    <a href="order_details.php?order_id=<?= $order['ID_Zamowienia'] ?>" class="btn">Szczegóły</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nie masz żadnych aktywnych zamówień.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 TechHouse. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>

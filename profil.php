<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    header("Location: login.php"); // Jeśli użytkownik nie jest zalogowany, przekierowanie na stronę logowania
    exit();
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Pobranie danych użytkownika z bazy danych
$query = "SELECT Imie, Nazwisko, Email, Adres FROM Uzytkownik WHERE ID_Uzytkownika = ?";
$params = [$id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Pobranie ostatnich zamówień użytkownika
$order_query = "SELECT ID_Zamowienia, Data_Zlozenia, Status, Laczna_Kwota FROM Zamowienie WHERE ID_Uzytkownika = ? ORDER BY Data_Zlozenia DESC LIMIT 5";
$order_stmt = sqlsrv_prepare($conn, $order_query, [$id_uzytkownika]);
sqlsrv_execute($order_stmt);
$orders = [];
while ($order_row = sqlsrv_fetch_array($order_stmt, SQLSRV_FETCH_ASSOC)) {
    $orders[] = $order_row;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoja Strona - Panel Użytkownika</title>
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <header class="header">
        <div class="logo">TechHouse</div>
        <nav class="user-menu">
            <a href="index.php">Strona główna</a>
            <a href="edit_profile.php">Edytuj Profil</a>
            <a href="logout.php">Wyloguj</a>
            <a href="cart.php">Koszyk</a>
            <a href="zamowienia.php">Zamówienia</a>
        </nav>
    </header>

    <main>
        <section class="profile-section">
            <h1>Witaj, <?= htmlspecialchars($user['Imie']) ?>!</h1>

            <h2>Twoje dane</h2>
            <ul>
                <li><strong>Imię i Nazwisko:</strong> <?= htmlspecialchars($user['Imie']) ?> <?= htmlspecialchars($user['Nazwisko']) ?></li>
                <li><strong>E-mail:</strong> <?= htmlspecialchars($user['Email']) ?></li>
                <li><strong>Adres:</strong> <?= htmlspecialchars($user['Adres']) ?></li>
            
            </ul>

            <h2>Ostatnie zamówienia</h2>
            <?php if (count($orders) > 0): ?>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID Zamówienia</th>
                            <th>Data Złożenia</th>
                            <th>Status</th>
                            <th>Łączna Kwota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['ID_Zamowienia']) ?></td>
                                <td><?= $order['Data_Zlozenia']->format('Y-m-d H:i:s') ?></td>
                                <td><?= htmlspecialchars($order['Status']) ?></td>
                                <td><?= number_format($order['Laczna_Kwota'], 2) ?> zł</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nie masz żadnych zamówień.</p>
            <?php endif; ?>

        </section>
    </main>

    <footer>
        <p>&copy; 2025 TechHouse. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>

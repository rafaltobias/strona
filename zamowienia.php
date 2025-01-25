<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    die("Musisz być zalogowany, aby zobaczyć swoje zamówienia.");
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Pobranie wybranego statusu z GET (domyślnie 'Nowe')
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Nowe';

// Zapytanie do bazy danych, aby pobrać zamówienia dla użytkownika w wybranym statusie
$query = "SELECT z.ID_Zamowienia, z.Data_Zlozenia, z.Status, z.Laczna_Kwota 
          FROM Zamowienie z
          WHERE z.ID_Uzytkownika = ? AND z.Status = ?
          ORDER BY z.Data_Zlozenia DESC";

$params = [$id_uzytkownika, $status_filter];
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
    <style>
        /* Ogólne style dla przycisków i linków */
        .status-navigation a {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 15px;
            background-color: #f8f9fa;  /* Tło pasujące do reszty strony */
            color: #333;  /* Kolor tekstu */
            border: 1px solid #ddd;  /* Delikatna ramka */
            border-radius: 5px;  /* Zaokrąglone rogi */
            text-decoration: none;  /* Usunięcie podkreślenia */
            transition: background-color 0.3s ease, color 0.3s ease;  /* Animacja przy zmianie */
        }

        /* Styl aktywnego linku */
        .status-navigation a.active {
            background-color: #008000;  /* Tło dla aktywnego linku */
            color: #fff;  /* Kolor tekstu aktywnego linku */
            border-color: #008000;  /* Kolor ramki aktywnego linku */
        }

        /* Styl przycisków po najechaniu */
        .status-navigation a:hover {
            background-color: #e2e6ea;  /* Jasniejsze tło przy hover */
            color: #008000;  /* Zmiana koloru tekstu na zielony przy hover */
            border-color: #bbb;  /* Zmiana koloru ramki przy hover */
        }
    </style>
</head>
<body>
    <?php include "header.php";?>

    <main>
        <section class="orders-section">
            <h1>Twoje zamówienia</h1>

            <!-- Nawigacja do przełączania między statusami -->
            <div class="status-navigation">
                <a href="?status=Nowe" class="<?= $status_filter == 'Nowe' ? 'active' : '' ?>">Nowe</a>
                <a href="?status=W trakcie realizacji" class="<?= $status_filter == 'W trakcie realizacji' ? 'active' : '' ?>">W trakcie realizacji</a>
                <a href="?status=Wysłane" class="<?= $status_filter == 'Wysłane' ? 'active' : '' ?>">Wysłane</a>
                <a href="?status=Dostarczone" class="<?= $status_filter == 'Dostarczone' ? 'active' : '' ?>">Dostarczone</a>
                <a href="?status=Anulowane" class="<?= $status_filter == 'Anulowane' ? 'active' : '' ?>">Anulowane</a>
                <a href="?status=Opóźnione" class="<?= $status_filter == 'Opóźnione' ? 'active' : '' ?>">Opóźnione</a>
                <a href="?status=Zwrócone" class="<?= $status_filter == 'Zwrócone' ? 'active' : '' ?>">Zwrócone</a>
            </div>

            <?php if (count($orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Data Złożenia</th>
                            <th>Status</th>
                            <th>Łączna Kwota</th>
                            <th>Opcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
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
                <p>Nie masz żadnych zamówień w statusie "<?= htmlspecialchars($status_filter) ?>".</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 TechHouse. Wszelkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>

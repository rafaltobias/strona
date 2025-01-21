<?php
session_start();
include 'db.php'; // Plik połączenia z bazą danych

// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    header("Location: login.php"); // Przekierowanie do logowania, jeśli użytkownik nie jest zalogowany
    exit();
}

// Pobierz ID zalogowanego użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Pobierz zamówienia użytkownika z bazy danych
$query = "
    SELECT 
        ID_Zamowienia, 
        Data_Zlozenia, 
        Status, 
        Laczna_Kwota, 
        ID_Transakcji 
    FROM Zamowienie 
    WHERE ID_Uzytkownika = ?
";
$params = [$id_uzytkownika];

$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Zamówienia</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f4f4f4; }
        a { text-decoration: none; color: #008000; }
    </style>
</head>
<body>
    <h1>Moje Zamówienia</h1>

    <?php if (sqlsrv_has_rows($stmt)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Zamówienia</th>
                    <th>Data Złożenia</th>
                    <th>Status</th>
                    <th>Łączna Kwota</th>
                    <th>ID Transakcji</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ID_Zamowienia']) ?></td>
                        <td><?= htmlspecialchars($row['Data_Zlozenia']->format('Y-m-d H:i:s')) ?></td>
                        <td><?= htmlspecialchars($row['Status']) ?></td>
                        <td><?= number_format($row['Laczna_Kwota'], 2) ?> zł</td>
                        <td><?= htmlspecialchars($row['ID_Transakcji'] ?? 'Brak') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nie masz żadnych zamówień.</p>
    <?php endif; ?>

    <?php 
    // Zwolnienie zasobów
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    ?>
</body>
</html>

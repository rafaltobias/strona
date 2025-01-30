<?php
session_start();
include 'db.php';
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] < 2) {
    header('Location: index.php');
    exit();
}
// Pobranie listy użytkowników do wyboru w formularzu
$users_query = "SELECT ID_Uzytkownika, Imie FROM Uzytkownik";
$users_result = sqlsrv_query($conn, $users_query);
if ($users_result === false) die(print_r(sqlsrv_errors(), true));

// Obsługa dodawania (Create) zamówienia
if (isset($_POST['add'])) {
    $sql = "INSERT INTO Zamowienie (ID_Uzytkownika, Data_Zlozenia, Status, Laczna_Kwota) VALUES (?, GETDATE(), ?, ?)";
    $params = [
        $_POST['ID_Uzytkownika'],
        $_POST['Status'],
        $_POST['Laczna_Kwota']
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($result === false) {
        echo "<script>alert('Błądprzy dodawaniu!');</script>";
    }


    header("Location: admin_zamowienia.php");
    exit();
}


// Obsługa usuwania (Delete) zamówienia
if (isset($_GET['delete'])) {
    $id_value = $_GET['delete'];

    // Usuwanie powiązanych produktów w tabeli Zamowienie_Produkt
    $sql = "DELETE FROM Zamowienie_Produkt WHERE ID_Zamowienia = ?";
    $params = [$id_value];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    // Usuwanie zamówienia
    $sql = "DELETE FROM Zamowienie WHERE ID_Zamowienia = ?";
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));


    header("Location: admin_zamowienia.php");
    exit();
}

// Obsługa edycji (Update) zamówienia
if (isset($_POST['update'])) {
    $sql = "UPDATE Zamowienie SET ID_Uzytkownika = ?, Status = ?, Laczna_Kwota = ? WHERE ID_Zamowienia = ?";
    $params = [
        $_POST['ID_Uzytkownika'],
       // $_POST['Data_Zlozenia'],
        $_POST['Status'],
        $_POST['Laczna_Kwota'],
        $_POST['ID_Zamowienia']
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($result === false) {
        echo "<script>alert('Błąd!');</script>";
    }

    header("Location: admin_zamowienia.php");
    exit();
}

// Wyszukiwanie zamówień
$search_query = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $search_query = " WHERE ID_Zamowienia LIKE ? OR ID_Uzytkownika LIKE ? OR Status LIKE ?";
}

$query = "SELECT * FROM Zamowienie" . $search_query;
$params = [];
if ($search_query) {
    $params = ["%$search%", "%$search%", "%$search%"];
}
$result = sqlsrv_query($conn, $query, $params);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - Zamówienia</title>
    <link rel="stylesheet" href="css/admin_panel.css">
</head>
<body>
    <?php include "admin_header.php";?>
    <h1>Zamówienia</h1>

    <!-- Formularz wyszukiwania -->
    <div class="form-container">
        <form method="GET">
            <input type="text" name="search" placeholder="Wyszukaj..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit">Szukaj</button>
        </form>
    </div>

   <!-- Formularz dodawania zamówienia -->
   <div class="form-container">
        <form method="POST">
            <label for="ID_Uzytkownika">Użytkownik:</label>
            <select name="ID_Uzytkownika" required>
                <?php while ($user = sqlsrv_fetch_array($users_result, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?= $user['ID_Uzytkownika'] ?>">
                        <?= htmlspecialchars($user['Imie']) ?> (ID: <?= $user['ID_Uzytkownika'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="Status">Status:</label>
            <select name="Status" required>
                <option value="Nowe">Nowe</option>
                <option value="W trakcie realizacji">W trakcie realizacji</option>
                <option value="Wysłane">Wysłane</option>
                <option value="Dostarczone">Dostarczone</option>
                <option value="Anulowane">Anulowane</option>
                <option value="Opóźnione">Opóźnione</option>
                <option value="Zwrócone">Zwrócone</option>
            </select>

            <label for="Laczna_Kwota">Łączna Kwota:</label>
            <input type="number" name="Laczna_Kwota" placeholder="Łączna Kwota" step="0.01" required min="0.01">

            <button type="submit" name="add">Dodaj Zamówienie</button>
        </form>
    </div>


    <!-- Tabela zamówień -->
    <table>
        <thead>
            <tr>
                <th>ID Zamówienia</th>
                <th>ID Użytkownika</th>
                <th>Data Złożenia</th>
                <th>Status</th>
                <th>Łączna Kwota</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <form method="POST">
                    <td><?= $row['ID_Zamowienia'] ?></td>
                    <td>
                        <?= $row['ID_Uzytkownika'] ?>
                        <input type="hidden" name="ID_Uzytkownika" value="<?= $row['ID_Uzytkownika'] ?>" required>
                    </td>

                    <!-- Zablokowanie możliwości edycji daty złożenia -->
                    <td><input type="datetime-local" name="Data_Zlozenia" value="<?= $row['Data_Zlozenia']->format('Y-m-d\TH:i:s') ?>" disabled required></td>

                    <td>
                        <select name="Status" required>
                            <option value="Nowe" <?= $row['Status'] == 'Nowe' ? 'selected' : '' ?>>Nowe</option>
                            <option value="W trakcie realizacji" <?= $row['Status'] == 'W trakcie realizacji' ? 'selected' : '' ?>>W trakcie realizacji</option>
                            <option value="Wysłane" <?= $row['Status'] == 'Wysłane' ? 'selected' : '' ?>>Wysłane</option>
                            <option value="Dostarczone" <?= $row['Status'] == 'Dostarczone' ? 'selected' : '' ?>>Dostarczone</option>
                            <option value="Anulowane" <?= $row['Status'] == 'Anulowane' ? 'selected' : '' ?>>Anulowane</option>
                            <option value="Opóźnione" <?= $row['Status'] == 'Opóźnione' ? 'selected' : '' ?>>Opóźnione</option>
                            <option value="Zwrócone" <?= $row['Status'] == 'Zwrócone' ? 'selected' : '' ?>>Zwrócone</option>
                        </select>
                    </td>

                    <td><input type="number" name="Laczna_Kwota" value="<?= $row['Laczna_Kwota'] ?>" step="0.01" required min = "0.01"></td>
                    <td>
                        <?php if ($_SESSION['ID_Uprawnienia'] >= 3): ?> <!-- Tylko użytkownicy z uprawnieniem >= 3 mogą usuwać -->
                            <button type="submit" name="update">Zaktualizuj</button>
                            <a href="?delete=<?= htmlspecialchars($row['ID_Zamowienia']) ?>" onclick="return confirm('Na pewno usunąć?')">Usuń</a>
                        <?php else: ?>
                            <button type="submit" name="update">Zaktualizuj</button>
                        <?php endif; ?>
                    </td>
                    <input type="hidden" name="ID_Zamowienia" value="<?= $row['ID_Zamowienia'] ?>">
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php 
    // Zwolnienie zasobów
    sqlsrv_free_stmt($result);
    sqlsrv_close($conn);
    ?>
</body>
<br>
    <a href="admin.php"><button>Powrót do panelu admina</button></a>


    <!-- Skrypt JavaScript do ustawienia dzisiejszej daty -->
<script>
    window.onload = function() {
        // Pobranie dzisiejszej daty w formacie 'YYYY-MM-DDTHH:MM'
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = today.getMonth() + 1; // Miesiące są indeksowane od 0
        var dd = today.getDate();
        var hh = today.getHours();
        var min = today.getMinutes();

        // Dodajemy zera przed jedną cyfrą w miesiącu, dniu, godzinach i minutach, jeśli to konieczne
        if (mm < 10) mm = '0' + mm;
        if (dd < 10) dd = '0' + dd;
        if (hh < 10) hh = '0' + hh;
        if (min < 10) min = '0' + min;

        // Ustawiamy pole formularza na dzisiejszą datę
        var datetime = yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + min;
        document.getElementById('data_zlozenia').value = datetime;

        // Zablokowanie pola daty do edycji
        document.getElementById('data_zlozenia').disabled = true;
    }
</script>
</html>
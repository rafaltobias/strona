<?php
session_start();
include 'db.php';

// Pobranie tabeli z parametru GET lub POST
$table = isset($_GET['table']) ? $_GET['table'] : '';
$id_field = "ID_" . ucfirst($table); // Automatyczne określenie pola ID (np. "ID_Zamowienia")

// Obsługa dodawania (Create) zamówienia
if (isset($_POST['add'])) {
    switch ($table) {
        case 'Zamowienie':
            $sql = "INSERT INTO Zamowienie (ID_Uzytkownika, Data_Zlozenia, Status, Laczna_Kwota) VALUES (?, ?, ?, ?)";
            $params = [
                $_POST['ID_Uzytkownika'],
                $_POST['Data_Zlozenia'],
                $_POST['Status'],
                $_POST['Laczna_Kwota']
            ];
            break;
        
        // Możesz dodać inne tabele w przyszłości
        default:
            die("Nieobsługiwana tabela!");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    header("Location: admin.php?table=$table");
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
    switch ($table) {
        case 'Zamowienie':
            $sql = "UPDATE Zamowienie SET ID_Uzytkownika = ?, Data_Zlozenia = ?, Status = ?, Laczna_Kwota = ? WHERE ID_Zamowienia = ?";
            $params = [
                $_POST['ID_Uzytkownika'],
                $_POST['Data_Zlozenia'],
                $_POST['Status'],
                $_POST['Laczna_Kwota'],
                $_POST['ID_Zamowienia']
            ];
            break;

        default:
            die("Nieobsługiwana tabela!");
    }

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) die(print_r(sqlsrv_errors(), true));

    header("Location: admin.php?table=$table");
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
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f4f4f4; }
        .form-container { margin-bottom: 20px; }
        .form-container input, select, button {
            padding: 10px; margin: 5px; font-size: 16px; width: calc(100% - 22px);
        }
        .form-container button { background-color: #008000; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Panel Administratora - Zamówienia</h1>

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
            <input type="number" name="ID_Uzytkownika" placeholder="ID Użytkownika" required>
            <input type="datetime-local" name="Data_Zlozenia" placeholder="Data Złożenia" required>
            <input type="text" name="Status" placeholder="Status" required>
            <input type="number" name="Laczna_Kwota" placeholder="Łączna Kwota" step="0.01" required>
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
                    <td><input type="number" name="ID_Uzytkownika" value="<?= $row['ID_Uzytkownika'] ?>" required></td>
                    <td><input type="datetime-local" name="Data_Zlozenia" value="<?= $row['Data_Zlozenia']->format('Y-m-d\TH:i:s') ?>" required></td>
                    <td><input type="text" name="Status" value="<?= htmlspecialchars($row['Status']) ?>" required></td>
                    <td><input type="number" name="Laczna_Kwota" value="<?= $row['Laczna_Kwota'] ?>" step="0.01" required></td>
                    <td>
                        <button type="submit" name="update">Zaktualizuj</button>
                        <a href="?delete=<?= htmlspecialchars($row['ID_Zamowienia']) ?>" onclick="return confirm('Na pewno usunąć?')">Usuń</a>
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
</html>

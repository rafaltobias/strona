<?php
session_start();
include 'db.php'; // Połączenie z bazą danych
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}
// Obsługa usuwania rekordu
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM Kategoria_Produktow WHERE ID_Kategorii = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$delete_id]);
    if (sqlsrv_execute($stmt)) {
        echo "Rekord usunięty.";
    } else {
        echo "Błąd podczas usuwania.";
    }
}

// Obsługa dodawania nowego rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $nazwa_kategorii = $_POST['Nazwa_Kategorii'];
    $opis = $_POST['Opis'];

    $query = "INSERT INTO Kategoria_Produktow (Nazwa_Kategorii, Opis) VALUES (?, ?)";
    $stmt = sqlsrv_prepare($conn, $query, [$nazwa_kategorii, $opis]);
    if (sqlsrv_execute($stmt)) {
        echo "Rekord dodany.";
    } else {
        echo "Błąd podczas dodawania.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Kategorie Produktów</title>
</head>
<body>
    <h1>Kategorie Produktów</h1>
    <form method="POST">
        <label>Nazwa Kategorii: <input type="text" name="Nazwa_Kategorii" required></label><br>
        <label>Opis: <input type="text" name="Opis"></label><br>
        <button type="submit" name="add">Dodaj</button>
    </form>

    <h2>Lista kategorii</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID Kategorii</th>
                <th>Nazwa Kategorii</th>
                <th>Opis</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT * FROM Kategoria_Produktow";
            $result = sqlsrv_query($conn, $query);
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>
                        <td>{$row['ID_Kategorii']}</td>
                        <td>{$row['Nazwa_Kategorii']}</td>
                        <td>{$row['Opis']}</td>
                        <td>
                            <a href='kategorie.php?delete_id={$row['ID_Kategorii']}'>Usuń</a>
                        </td>
                    </tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <a href="admin.php"><button>Powrót do panelu admina</button></a>
</body>
</html>

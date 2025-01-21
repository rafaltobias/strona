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
    $query = "DELETE FROM Koszyk WHERE ID_Koszyka = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$delete_id]);
    if (sqlsrv_execute($stmt)) {
        echo "Rekord usunięty.";
    } else {
        echo "Błąd podczas usuwania.";
    }
}

// Obsługa dodawania nowego rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $id_uzytkownika = $_POST['ID_Uzytkownika'];
    $data_utworzenia = $_POST['Data_Utworzenia'];

    $query = "INSERT INTO Koszyk (ID_Uzytkownika, Data_Utworzenia) VALUES (?, ?)";
    $stmt = sqlsrv_prepare($conn, $query, [$id_uzytkownika, $data_utworzenia]);
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
    <title>Admin Panel - Koszyk</title>
</head>
<body>
    <h1>Koszyk</h1>
    <form method="POST">
        <label>ID Użytkownika: <input type="text" name="ID_Uzytkownika"></label><br>
        <label>Data Utworzenia: <input type="datetime-local" name="Data_Utworzenia"></label><br>
        <button type="submit" name="add">Dodaj</button>
    </form>

    <h2>Lista koszyków</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID Koszyka</th>
                <th>ID Użytkownika</th>
                <th>Data Utworzenia</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT * FROM Koszyk";
            $result = sqlsrv_query($conn, $query);
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>
                        <td>{$row['ID_Koszyka']}</td>
                        <td>{$row['ID_Uzytkownika']}</td>
                        <td>{$row['Data_Utworzenia']->format('Y-m-d H:i:s')}</td>
                        <td>
                            <a href='koszyk.php?delete_id={$row['ID_Koszyka']}'>Usuń</a>
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

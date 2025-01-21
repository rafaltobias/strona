<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Obsługa usuwania rekordu
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM Koszyk_Produkt WHERE ID_Koszyk_Produkt = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$delete_id]);
    if (sqlsrv_execute($stmt)) {
        echo "Rekord usunięty.";
    } else {
        echo "Błąd podczas usuwania.";
    }
}

// Obsługa dodawania nowego rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $id_koszyka = $_POST['ID_Koszyka'];
    $id_produktu = $_POST['ID_Produktu'];
    $ilosc = $_POST['Ilosc'];

    $query = "INSERT INTO Koszyk_Produkt (ID_Koszyka, ID_Produktu, Ilosc) VALUES (?, ?, ?)";
    $stmt = sqlsrv_prepare($conn, $query, [$id_koszyka, $id_produktu, $ilosc]);
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
    <title>Admin Panel - Koszyk Produkt</title>
</head>
<body>
    <h1>Koszyk Produkt</h1>
    <form method="POST">
        <label>ID Koszyka: <input type="text" name="ID_Koszyka"></label><br>
        <label>ID Produktu: <input type="text" name="ID_Produktu"></label><br>
        <label>Ilość: <input type="number" name="Ilosc"></label><br>
        <button type="submit" name="add">Dodaj</button>
    </form>

    <h2>Lista produktów w koszykach</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID Koszyk Produkt</th>
                <th>ID Koszyka</th>
                <th>ID Produktu</th>
                <th>Ilość</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT * FROM Koszyk_Produkt";
            $result = sqlsrv_query($conn, $query);
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>
                        <td>{$row['ID_Koszyk_Produkt']}</td>
                        <td>{$row['ID_Koszyka']}</td>
                        <td>{$row['ID_Produktu']}</td>
                        <td>{$row['Ilosc']}</td>
                        <td>
                            <a href='koszyk_produkt.php?delete_id={$row['ID_Koszyk_Produkt']}'>Usuń</a>
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

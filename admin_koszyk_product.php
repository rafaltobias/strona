<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] < 3) {
    header('Location: index.php');
    exit();
}

$error_message = "";
$success_message = "";

// Pobieranie listy dostępnych koszyków
$query_koszyki = "SELECT ID_Koszyka FROM Koszyk";
$result_koszyki = sqlsrv_query($conn, $query_koszyki);

// Pobieranie listy dostępnych produktów
$query_produkty = "SELECT ID_Produktu, Nazwa_Produktu FROM Produkt";
$result_produkty = sqlsrv_query($conn, $query_produkty);

// Obsługa usuwania rekordu
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $query = "DELETE FROM Koszyk_Produkt WHERE ID_Koszyk_Produkt = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$delete_id]);
    if (sqlsrv_execute($stmt)) {
        $success_message = "Rekord usunięty.";
    } else {
        $error_message = "Błąd podczas usuwania.";
    }
}

// Obsługa dodawania nowego rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $id_koszyka = $_POST['ID_Koszyka'];
    $id_produktu = $_POST['ID_Produktu'];
    $ilosc = $_POST['Ilosc'];

    if (!is_numeric($id_koszyka) || !is_numeric($id_produktu) || !is_numeric($ilosc) || $ilosc <= 0) {
        $error_message = "Wszystkie pola muszą zawierać poprawne wartości.";
    } else {
        $query = "INSERT INTO Koszyk_Produkt (ID_Koszyka, ID_Produktu, Ilosc) VALUES (?, ?, ?)";
        $stmt = sqlsrv_prepare($conn, $query, [$id_koszyka, $id_produktu, $ilosc]);
        if (sqlsrv_execute($stmt)) {
            $success_message = "Rekord dodany.";
        } else {
            $error_message = "Błąd podczas dodawania.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Koszyk</title>
    <link rel="stylesheet" href="css/admin_panel.css">
</head>
<body>
    <header class="header">
        <?php include "admin_header.php"; ?>
    </header>
    
    <main>
        <h1>Panel Koszyka</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <h2>Dodaj produkt do koszyka</h2>
        <form method="POST">
            <label for="ID_Koszyka">Wybierz koszyk:</label>
            <select name="ID_Koszyka" required>
                <option value="">Wybierz koszyk</option>
                <?php while ($row = sqlsrv_fetch_array($result_koszyki, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?php echo $row['ID_Koszyka']; ?>"><?php echo "Koszyk " . $row['ID_Koszyka']; ?></option>
                <?php endwhile; ?>
            </select>

            <label for="ID_Produktu">Wybierz produkt:</label>
            <select name="ID_Produktu" required>
                <option value="">Wybierz produkt</option>
                <?php while ($row = sqlsrv_fetch_array($result_produkty, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?php echo $row['ID_Produktu']; ?>"><?php echo $row['Nazwa_Produktu']; ?></option>
                <?php endwhile; ?>
            </select>

            <label for="Ilosc">Ilość:</label>
            <input type="number" name="Ilosc" required min="1">

            <button type="submit" name="add">Dodaj</button>
        </form>

        <h2>Lista produktów w koszykach</h2>
        <table>
            <thead>
                <tr>
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
                    ?>
                    <tr>
                        <td><?php echo $row['ID_Koszyka']; ?></td>
                        <td><?php echo $row['ID_Produktu']; ?></td>
                        <td><?php echo $row['Ilosc']; ?></td>
                        <td>
                            <form method="POST">
                                <button type="submit" name="delete_id" value="<?php echo $row['ID_Koszyk_Produkt']; ?>" onclick="return confirm('Czy na pewno chcesz usunąć ten produkt z koszyka?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <br>
        <a href="admin.php"><button>Powrót do panelu admina</button></a>
    </main>
</body>
</html>

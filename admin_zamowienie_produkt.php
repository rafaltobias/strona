<?php
session_start();
include 'db.php'; 

if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

$error_message = "";
$success_message = "";

// Pobranie dostępnych zamówień i produktów do dropdownów
$zamowienia_query = "SELECT ID_Zamowienia FROM Zamowienie";
$zamowienia_result = sqlsrv_query($conn, $zamowienia_query);
if ($zamowienia_result === false) {
    $error_message = "Błąd pobierania zamówień: " . print_r(sqlsrv_errors(), true);
}

$produkty_query = "SELECT ID_Produktu, Nazwa_Produktu FROM Produkt";
$produkty_result = sqlsrv_query($conn, $produkty_query);
if ($produkty_result === false) {
    $error_message = "Błąd pobierania produktów: " . print_r(sqlsrv_errors(), true);
}

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $id_zamowienia = $_POST['id_zamowienia'];
        $id_produktu = $_POST['id_produktu'];
        $ilosc = $_POST['ilosc'];
        $cena = $_POST['cena'];

        if (isset($_POST['create'])) {
            $query = "INSERT INTO Zamowienie_Produkt (ID_Zamowienia, ID_Produktu, Ilosc, Cena) VALUES (?, ?, ?, ?)";
            $params = array($id_zamowienia, $id_produktu, $ilosc, $cena);
        } elseif (isset($_POST['update'])) {
            $query = "UPDATE Zamowienie_Produkt SET Ilosc = ?, Cena = ? WHERE ID_Zamowienia = ? AND ID_Produktu = ?";
            $params = array($ilosc, $cena, $id_zamowienia, $id_produktu);
        }

        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            $error_message = "Wystąpił błąd: " . print_r(sqlsrv_errors(), true);
        } else {
            $success_message = isset($_POST['create']) ? "Rekord został dodany." : "Rekord został zaktualizowany.";
        }
    } elseif (isset($_POST['delete'])) {
        $id_zamowienia = $_POST['id_zamowienia'];
        $id_produktu = $_POST['id_produktu'];
        $query = "DELETE FROM Zamowienie_Produkt WHERE ID_Zamowienia = ? AND ID_Produktu = ?";
        $params = array($id_zamowienia, $id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt === false) {
            $error_message = "Wystąpił błąd: " . print_r(sqlsrv_errors(), true);
        } else {
            $success_message = "Rekord został pomyślnie usunięty.";
        }
    }
}

// Pobranie listy zamówień i produktów
$query = "SELECT * FROM Zamowienie_Produkt";
$result = sqlsrv_query($conn, $query);
if ($result === false) {
    $error_message = "Wystąpił błąd: " . print_r(sqlsrv_errors(), true);
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Zamówienie Produkt</title>
    <link rel="stylesheet" href="css/admin_panel.css">
    <script>
        // Wyświetlanie błędu w alert
        <?php if (!empty($error_message)): ?>
            alert("<?php echo addslashes($error_message); ?>");
        <?php endif; ?>
    </script>
</head>
<body>
    <header class="header">
        <?php include "admin_header.php"; ?>
    </header>
    <main>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Formularz dodawania nowego rekordu -->
        <h2>Dodaj nowy produkt do zamówienia</h2>
        <div class="form-container">
            <form method="post">
                <label for="id_zamowienia">ID Zamówienia:</label>
                <select id="id_zamowienia" name="id_zamowienia" required>
                    <?php while ($row = sqlsrv_fetch_array($zamowienia_result, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?= $row['ID_Zamowienia'] ?>"><?= $row['ID_Zamowienia'] ?></option>
                    <?php endwhile; ?>
                </select>
                
                <label for="id_produktu">ID Produktu:</label>
                <select id="id_produktu" name="id_produktu" required>
                    <?php while ($row = sqlsrv_fetch_array($produkty_result, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?= $row['ID_Produktu'] ?>"><?= $row['ID_Produktu'] ?> - <?= htmlspecialchars($row['Nazwa_Produktu']) ?></option>
                    <?php endwhile; ?>
                </select>
                
                <label for="ilosc">Ilość:</label>
                <input type="number" id="ilosc" name="ilosc" required min="1">
                
                <label for="cena">Cena:</label>
                <input type="number" step="0.01" id="cena" name="cena" required min="0.01">
                
                <input type="submit" name="create" value="Dodaj">
            </form>
        </div>

        <!-- Tabela rekordów -->
        <table>
            <thead>
                <tr>
                    <th>ID Zamówienia</th>
                    <th>ID Produktu</th>
                    <th>Ilość</th>
                    <th>Cena</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <form method="post">
                        <td><?php echo $row['ID_Zamowienia']; ?></td>
                        <td><?php echo $row['ID_Produktu']; ?></td>
                        <td><input type="number" name="ilosc" value="<?php echo $row['Ilosc']; ?>" required min="1"></td>
                        <td><input type="number" step="0.01" name="cena" value="<?php echo $row['Cena']; ?>" required min="0.01"></td>
                        <td>
                            <input type="hidden" name="id_zamowienia" value="<?php echo $row['ID_Zamowienia']; ?>">
                            <input type="hidden" name="id_produktu" value="<?php echo $row['ID_Produktu']; ?>">
                            <button type="submit" name="update">Aktualizuj</button>
                            <button type="submit" name="delete">Usuń</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <br>
        <a href="admin.php"><button>Powrót do panelu admina</button></a>
    </main>
</body>
</html>

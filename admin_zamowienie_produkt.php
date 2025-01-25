<?php
session_start();
include 'db.php'; 

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

$error_message = ""; // Zmienna na komunikaty błędów
$success_message = ""; // Zmienna na komunikaty sukcesu

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $id_zamowienia = $_POST['id_zamowienia'];
        $id_produktu = $_POST['id_produktu'];
        $ilosc = $_POST['ilosc'];
        $cena = $_POST['cena'];

        // Sprawdzenie, czy produkt w zamówieniu już istnieje
        $check_query = "SELECT COUNT(*) AS count FROM Zamowienie_Produkt WHERE ID_Zamowienia = ? AND ID_Produktu = ?";
        $params_check = [$id_zamowienia, $id_produktu];
        if (isset($_POST['update'])) {
            // Wykluczamy aktualny rekord z walidacji
            $id_zamowienie_produkt = $_POST['id_zamowienie_produkt'];
            $check_query .= " AND NOT EXISTS (SELECT 1 FROM Zamowienie_Produkt WHERE ID_Zamowienia_Produkt = ?)";
            $params_check[] = $id_zamowienie_produkt;
        }
        $check_stmt = sqlsrv_query($conn, $check_query, $params_check);
        if ($check_stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $check_result = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
        
        if ($check_result['count'] > 0) {
            $error_message = "Produkt o podanym ID już istnieje w tym zamówieniu.";
        } else {
            if (isset($_POST['create'])) {
                // Dodawanie nowego rekordu
                $query = "INSERT INTO Zamowienie_Produkt (ID_Zamowienia, ID_Produktu, Ilosc, Cena) VALUES (?, ?, ?, ?)";
                $params = array($id_zamowienia, $id_produktu, $ilosc, $cena);
            } elseif (isset($_POST['update'])) {
                // Aktualizacja istniejącego rekordu
                $query = "UPDATE Zamowienie_Produkt SET Ilosc = ?, Cena = ? WHERE ID_Zamowienia = ? AND ID_Produktu = ?";
                $params = array($ilosc, $cena, $id_zamowienia, $id_produktu);
            }

            $stmt = sqlsrv_query($conn, $query, $params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            $success_message = isset($_POST['create']) ? "Rekord został dodany." : "Rekord został zaktualizowany.";
        }
    } elseif (isset($_POST['delete'])) {
        $id_zamowienia = $_POST['id_zamowienia'];
        $id_produktu = $_POST['id_produktu'];
        $query = "DELETE FROM Zamowienie_Produkt WHERE ID_Zamowienia = ? AND ID_Produktu = ?";
        $params = array($id_zamowienia, $id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);
    
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        } else {
            $success_message = "Rekord został pomyślnie usunięty.";
        }
    }
}

// Pobieranie wszystkich rekordów z bazy danych
$query = "SELECT * FROM Zamowienie_Produkt";
$result = sqlsrv_query($conn, $query);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Zamówienie Produkt</title>
    <link rel="stylesheet" href="css/admin_panel.css">
</head>
<body>
    <header class="header">
        <?php include "admin_header.php"; ?>
    </header>
    <main>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Formularz dodawania nowego rekordu -->
        <h2>Dodaj nowy produkt do zamówienia</h2>
        <div class="form-container">
            <form method="post">
                <label for="id_zamowienia">ID Zamówienia:</label>
                <input type="number" id="id_zamowienia" name="id_zamowienia" required>
                
                <label for="id_produktu">ID Produktu:</label>
                <input type="number" id="id_produktu" name="id_produktu" required>
                
                <label for="ilosc">Ilość:</label>
                <input type="number" id="ilosc" name="ilosc" required>
                
                <label for="cena">Cena:</label>
                <input type="number" step="0.01" id="cena" name="cena" required>
                
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
                        <td><input type="number" name="ilosc" value="<?php echo $row['Ilosc']; ?>" required></td>
                        <td><input type="number" step="0.01" name="cena" value="<?php echo $row['Cena']; ?>" required></td>
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

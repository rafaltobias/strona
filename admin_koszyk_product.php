<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

$error_message = ""; // Zmienna na komunikaty błędów
$success_message = ""; // Zmienna na komunikaty sukcesu

// Obsługa usuwania rekordu
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $query = "DELETE FROM Koszyk_Produkt WHERE ID_Koszyk_Produkt = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$delete_id]);
    if (sqlsrv_execute($stmt)) {
        $success_message = "Rekord został usunięty.";
    } else {
        $error_message = "Błąd podczas usuwania.";
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
        $success_message = "Rekord został dodany.";
    } else {
        $error_message = "Błąd podczas dodawania.";
    }
}

// Obsługa aktualizacji rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id_koszyk_produkt = $_POST['ID_Koszyk_Produkt'];
    $id_koszyka = $_POST['ID_Koszyka'];
    $id_produktu = $_POST['ID_Produktu'];
    $ilosc = $_POST['Ilosc'];

    $query = "UPDATE Koszyk_Produkt SET ID_Koszyka = ?, ID_Produktu = ?, Ilosc = ? WHERE ID_Koszyk_Produkt = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$id_koszyka, $id_produktu, $ilosc, $id_koszyk_produkt]);
    if (sqlsrv_execute($stmt)) {
        $success_message = "Rekord został zaktualizowany.";
    } else {
        $error_message = "Błąd podczas aktualizacji.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Koszyk Produkt</title>
    <link rel="stylesheet" href="css/admin_panel.css">
</head>
<body>
    <header>
        <?php include 'admin_header.php'; ?>
    </header>
    <main>
        <h1>Koszyk Produkt</h1>

        <!-- Komunikaty sukcesu i błędów -->
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Formularz dodawania nowego rekordu -->
        <h2>Dodaj nowy produkt do koszyka</h2>
        <form method="POST">
            <label>ID Koszyka: <input type="text" name="ID_Koszyka" required></label><br>
            <label>ID Produktu: <input type="text" name="ID_Produktu" required></label><br>
            <label>Ilość: <input type="number" name="Ilosc" required></label><br>
            <button type="submit" name="add">Dodaj</button>
        </form>

        <!-- Tabela z danymi -->
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
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <form method="POST">
                            <td><?php echo $row['ID_Koszyk_Produkt']; ?></td>
                            <td><input type="text" name="ID_Koszyka" value="<?php echo $row['ID_Koszyka']; ?>" required readonly></td>
                            <td><input type="text" name="ID_Produktu" value="<?php echo $row['ID_Produktu']; ?>" required readonly></td>
                            <td><input type="number" name="Ilosc" value="<?php echo $row['Ilosc']; ?>" required></td>
                            <td>
                                <input type="hidden" name="ID_Koszyk_Produkt" value="<?php echo $row['ID_Koszyk_Produkt']; ?>">
                                <button type="submit" name="update">Aktualizuj</button>
                                <a href="koszyk_produkt.php?delete_id=<?php echo $row['ID_Koszyk_Produkt']; ?>">Usuń</a>
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

<?php
session_start();
include 'db.php'; 

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Pobieranie dostępnych kategorii z bazy danych
$category_query = "SELECT ID_Kategorii, Nazwa_Kategorii FROM Kategoria_produktow";
$category_result = sqlsrv_query($conn, $category_query);

if ($category_result === false) {
    die(print_r(sqlsrv_errors(), true));
}

$categories = [];
while ($category_row = sqlsrv_fetch_array($category_result, SQLSRV_FETCH_ASSOC)) {
    $categories[] = $category_row;
}

$error_message = ""; // Zmienna na komunikaty błędów
$success_message = ""; // Zmienna na komunikaty sukcesu

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $nazwa_produktu = $_POST['nazwa_produktu'];
        $cena = $_POST['cena'];
        $stan_magazynowy = $_POST['stan_magazynowy'];
        $id_kategorii = $_POST['id_kategorii'];
        $opis = $_POST['opis'];

        // Sprawdzenie, czy produkt o tej nazwie już istnieje
        $check_query = "SELECT COUNT(*) AS count FROM Produkt WHERE Nazwa_Produktu = ?";
        $params_check = [$nazwa_produktu];
        if (isset($_POST['update'])) {
            // Wykluczamy aktualny produkt z walidacji
            $id_produktu = $_POST['id_produktu'];
            $check_query .= " AND ID_Produktu != ?";
            $params_check[] = $id_produktu;
        }
        $check_stmt = sqlsrv_query($conn, $check_query, $params_check);
        if ($check_stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $check_result = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
        if (!is_numeric($cena) || $cena <= 0) {
            $error_message = "Cena musi być liczbą oraz być większa od zera.";
        }elseif(!is_numeric($stan_magazynowy)){
            $error_message = "Stan magazynowy musi być liczbą";
        }
        elseif ($check_result['count'] > 0) {
            $error_message = "Produkt o nazwie '$nazwa_produktu' już istnieje.";
        } else {
            if (isset($_POST['create'])) {
                // Dodawanie nowego produktu
                $query = "INSERT INTO Produkt (Nazwa_Produktu, Cena, Stan_Magazynowy, ID_Kategorii, Opis, Aktywny) VALUES (?, ?, ?, ?, ?, 1)";
                $params = array($nazwa_produktu, $cena, $stan_magazynowy, $id_kategorii, $opis);
            } elseif (isset($_POST['update'])) {
                // Aktualizacja istniejącego produktu
                $query = "UPDATE Produkt SET Nazwa_Produktu = ?, Cena = ?, Stan_Magazynowy = ?, ID_Kategorii = ?, Opis = ? WHERE ID_Produktu = ?";
                $params = array($nazwa_produktu, $cena, $stan_magazynowy, $id_kategorii, $opis, $id_produktu);
            }

            $stmt = sqlsrv_query($conn, $query, $params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            $success_message = isset($_POST['create']) ? "Produkt został dodany." : "Produkt został zaktualizowany.";
        }
    } elseif (isset($_POST['delete'])) {
        $id_produktu = $_POST['id_produktu'];
        $query = "DELETE FROM Produkt WHERE ID_Produktu = ?";
        $params = array($id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);
    
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            if (isset($errors[0]['code']) && $errors[0]['code'] == 547) {
                $error_message = "Nie można usunąć produktu, ponieważ jest powiązany z zamówieniami.";
            } else {
                $error_message = "Błąd podczas usuwania produktu.";
            }
        } else {
            $success_message = "Produkt został pomyślnie usunięty.";
        }
    } elseif (isset($_POST['deactivate'])) {
        $id_produktu = $_POST['id_produktu'];
        $query = "UPDATE Produkt SET Aktywny = 0 WHERE ID_Produktu = ?";
        $params = array($id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $success_message = "Produkt został dezaktywowany.";
    } elseif (isset($_POST['activate'])) {
        $id_produktu = $_POST['id_produktu'];
        $query = "UPDATE Produkt SET Aktywny = 1 WHERE ID_Produktu = ?";
        $params = array($id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $success_message = "Produkt został aktywowany.";
    }
}

// Pobieranie wszystkich produktów z bazy danych
$query = "SELECT * FROM Produkt";
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
    <title>Admin - Produkty</title>
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

        <!-- Formularz wyszukiwania produktów -->
        <div class="form-container">
            <form method="GET">
                <input type="text" name="search" placeholder="Wyszukaj produkt..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit">Szukaj</button>
            </form>
        </div>

        <!-- Formularz dodawania nowego produktu -->
        <h2>Dodaj nowy produkt</h2>
        <div class="form-container">
            <form method="post">
                <label for="nazwa_produktu">Nazwa Produktu:</label>
                <input type="text" id="nazwa_produktu" name="nazwa_produktu" required>
                
                <label for="cena">Cena:</label>
                <input type="number" id="cena" name="cena" required>
                
                <label for="stan_magazynowy">Stan Magazynowy:</label>
                <input type="number" id="stan_magazynowy" name="stan_magazynowy" required>
                
                <label for="id_kategorii">ID Kategorii:</label>
                <select id="id_kategorii" name="id_kategorii" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['ID_Kategorii']; ?>">
                            <?php echo $category['Nazwa_Kategorii']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label for="opis">Opis:</label>
                <input type="text" id="opis" name="opis" required>
                
                <input type="submit" name="create" value="Dodaj">
            </form>
        </div>

        <!-- Tabela produktów -->
        <h2>Lista produktów</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa Produktu</th>
                    <th>Cena</th>
                    <th>Stan Magazynowy</th>
                    <th>ID Kategorii</th>
                    <th>Opis</th>
                    <th>Aktywny</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <form method="post">
                        <td><?php echo $row['ID_Produktu']; ?></td>
                        <td><input type="text" name="nazwa_produktu" value="<?php echo $row['Nazwa_Produktu']; ?>" required></td>
                        <td><input type="number" name="cena" value="<?php echo $row['Cena']; ?>" required></td>
                        <td><input type="number" name="stan_magazynowy" value="<?php echo $row['Stan_Magazynowy']; ?>" required></td>
                        <td>
                            <select name="id_kategorii" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['ID_Kategorii']; ?>" <?php if ($row['ID_Kategorii'] == $category['ID_Kategorii']) echo 'selected'; ?>>
                                        <?php echo $category['Nazwa_Kategorii']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="text" name="opis" value="<?php echo $row['Opis']; ?>" required></td>
                        <td><?php echo $row['Aktywny'] ? 'Tak' : 'Nie'; ?></td>
                        <td>
                            <input type="hidden" name="id_produktu" value="<?php echo $row['ID_Produktu']; ?>">
                            <button type="submit" name="update">Aktualizuj</button>
                            <?php if ($row['Aktywny']): ?>
                                <button type="submit" name="deactivate">Dezaktywuj</button>
                            <?php else: ?>
                                <button type="submit" name="activate">Aktywuj</button>
                            <?php endif; ?>
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

<?php
session_start();
include 'db.php'; 

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Pobieranie dostępnych kategorii z bazy danych
$category_query = "SELECT ID_Kategorii, Nazwa_Kategorii FROM Kategoria_Produktow";
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
        $nazwa_kategorii = $_POST['nazwa_kategorii'];
        $opis = $_POST['opis'];

        // Sprawdzenie, czy kategoria o tej nazwie już istnieje
        $check_query = "SELECT COUNT(*) AS count FROM Kategoria_Produktow WHERE Nazwa_Kategorii = ?";
        $params_check = [$nazwa_kategorii];
        if (isset($_POST['update'])) {
            // Wykluczamy aktualną kategorię z walidacji
            $id_kategorii = $_POST['id_kategorii'];
            $check_query .= " AND ID_Kategorii != ?";
            $params_check[] = $id_kategorii;
        }
        $check_stmt = sqlsrv_query($conn, $check_query, $params_check);
        if ($check_stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $check_result = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
        
        if ($check_result['count'] > 0) {
            $error_message = "Kategoria o nazwie '$nazwa_kategorii' już istnieje.";
        } else {
            if (isset($_POST['create'])) {
                // Dodawanie nowej kategorii
                $query = "INSERT INTO Kategoria_Produktow (Nazwa_Kategorii, Opis) VALUES (?, ?)";
                $params = array($nazwa_kategorii, $opis);
            } elseif (isset($_POST['update'])) {
                // Aktualizacja istniejącej kategorii
                $query = "UPDATE Kategoria_Produktow SET Nazwa_Kategorii = ?, Opis = ? WHERE ID_Kategorii = ?";
                $params = array($nazwa_kategorii, $opis, $id_kategorii);
            }

            $stmt = sqlsrv_query($conn, $query, $params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            $success_message = isset($_POST['create']) ? "Kategoria została dodana." : "Kategoria została zaktualizowana.";
        }
    } elseif (isset($_POST['delete'])) {
        $id_kategorii = $_POST['id_kategorii'];
        $query = "DELETE FROM Kategoria_Produktow WHERE ID_Kategorii = ?";
        $params = array($id_kategorii);
        $stmt = sqlsrv_query($conn, $query, $params);
    
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            if (isset($errors[0]['code']) && $errors[0]['code'] == 547) {
                $error_message = "Nie można usunąć kategorii, ponieważ są do niej przypisane produkty.";
            } else {
                die(print_r(sqlsrv_errors(), true));
            }
        } else {
            $success_message = "Kategoria została pomyślnie usunięta.";
        }
    }
}

// Pobieranie wszystkich kategorii z bazy danych
$query = "SELECT * FROM Kategoria_Produktow";
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
    <title>Admin - Kategorie Produktów</title>
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

        <!-- Formularz dodawania nowej kategorii -->
        <h2>Dodaj nową kategorię</h2>
        <div class="form-container">
            <form method="post">
                <label for="nazwa_kategorii">Nazwa Kategorii:</label>
                <input type="text" id="nazwa_kategorii" name="nazwa_kategorii" required>
                
                <label for="opis">Opis:</label>
                <input type="text" id="opis" name="opis" required>
                
                <input type="submit" name="create" value="Dodaj">
            </form>
        </div>

        <!-- Tabela kategorii -->
        <table>
            <thead>
                <tr>
                    <th>ID Kategorii</th>
                    <th>Nazwa Kategorii</th>
                    <th>Opis</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <form method="post">
                        <td><?php echo $row['ID_Kategorii']; ?></td>
                        <td><input type="text" name="nazwa_kategorii" value="<?php echo $row['Nazwa_Kategorii']; ?>" required></td>
                        <td><input type="text" name="opis" value="<?php echo $row['Opis']; ?>" required></td>
                        <td>
                            <input type="hidden" name="id_kategorii" value="<?php echo $row['ID_Kategorii']; ?>">
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

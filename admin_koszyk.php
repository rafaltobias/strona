<?php
session_start();
include 'db.php'; // Połączenie z bazą danych
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] < 3) {
    header('Location: index.php');
    exit();
}
$error_message = ""; // Zmienna na komunikaty błędów
$success_message = ""; // Zmienna na komunikaty sukcesu

// Obsługa usuwania rekordu
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $query = "DELETE FROM Koszyk WHERE ID_Koszyka = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$delete_id]);
    if (sqlsrv_execute($stmt)) {
        $success_message = "Rekord usunięty.";
    } else {
        $error_message = "Błąd podczas usuwania.";
    }
}

// Obsługa dodawania nowego rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $id_uzytkownika = $_POST['ID_Uzytkownika'];
    $data_utworzenia = $_POST['Data_Utworzenia'];

    // Walidacja danych
    if (!is_numeric($id_uzytkownika) || empty($data_utworzenia)) {
        $error_message = "ID Użytkownika musi być liczbą, a Data Utworzenia nie może być pusta.";
    } else {
        $query = "INSERT INTO Koszyk (ID_Uzytkownika, Data_Utworzenia) VALUES (?, ?)";
        $stmt = sqlsrv_prepare($conn, $query, [$id_uzytkownika, $data_utworzenia]);
        if (sqlsrv_execute($stmt)) {
            $success_message = "Rekord dodany.";
        } else {
            $error_message = "Błąd podczas dodawania.";
        }
    }
}

// Obsługa aktualizacji rekordu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id_koszyka = $_POST['ID_Koszyka'];
    $id_uzytkownika = $_POST['ID_Uzytkownika'];
    $data_utworzenia = $_POST['Data_Utworzenia'];

    // Walidacja danych
    if (!is_numeric($id_uzytkownika) || empty($data_utworzenia)) {
        $error_message = "ID Użytkownika musi być liczbą, a Data Utworzenia nie może być pusta.";
    } else {
        $query = "UPDATE Koszyk SET ID_Uzytkownika = ?, Data_Utworzenia = ? WHERE ID_Koszyka = ?";
        $stmt = sqlsrv_prepare($conn, $query, [$id_uzytkownika, $data_utworzenia, $id_koszyka]);
        if (sqlsrv_execute($stmt)) {
            $success_message = "Rekord zaktualizowany.";
        } else {
            $error_message = "Błąd podczas aktualizacji.";
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
    <link rel="stylesheet" href="css/admin_panel.css"> <!-- Dodaj styl CSS -->
</head>
<body>
    <header class="header">
        <?php include "admin_header.php"; ?>
    </header>
    
    <main>
        <h1>Panel Koszyka</h1>

        <!-- Komunikaty o błędach i sukcesie -->
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Tabela koszyków -->
        <h2>Lista Koszyków</h2>
        <table>
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
                    ?>
                    <tr>
                        <td><?php echo $row['ID_Koszyka']; ?></td>
                        <td><?php echo $row['ID_Uzytkownika']; ?></td>
                        <td><?php echo $row['Data_Utworzenia']->format('Y-m-d H:i'); ?></td>
                        <td>
                            <form method="POST">
                                <button type="submit" name="delete_id" value="<?php echo $row['ID_Koszyka']; ?>" onclick="return confirm('Czy na pewno chcesz usunąć ten koszyk?')">Usuń</button>
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

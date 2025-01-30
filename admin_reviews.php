<?php
session_start();
include 'db.php'; 

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] <2) {
    header('Location: index.php');
    exit();
}

$error_message = ""; // Zmienna na komunikaty błędów
$success_message = ""; // Zmienna na komunikaty sukcesu

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $id_produktu = $_POST['id_produktu'];
        $id_uzytkownika = $_POST['id_uzytkownika'];
        $ocena = $_POST['ocena'];
        $komentarz = $_POST['komentarz'];

        // Walidacja oceny
        if ($ocena < 1 || $ocena > 5) {
            $error_message = "Ocena musi być liczbą od 1 do 5.";
        } else {
            if (isset($_POST['create'])) {
                // Dodawanie nowej recenzji
                $query = "INSERT INTO Recenzje (ID_Produktu, ID_Uzytkownika, Ocena, Komentarz) VALUES (?, ?, ?, ?)";
                $params = [$id_produktu, $id_uzytkownika, $ocena, $komentarz];
            } elseif (isset($_POST['update'])) {
                // Aktualizacja istniejącej recenzji
                $id_recenzji = $_POST['id_recenzji'];
                $query = "UPDATE Recenzje SET ID_Produktu = ?, ID_Uzytkownika = ?, Ocena = ?, Komentarz = ? WHERE ID_Recenzji = ?";
                $params = [$id_produktu, $id_uzytkownika, $ocena, $komentarz, $id_recenzji];
            }

            $stmt = sqlsrv_query($conn, $query, $params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            $success_message = isset($_POST['create']) ? "Recenzja została dodana." : "Recenzja została zaktualizowana.";
        }
    } elseif (isset($_POST['delete'])) {
        $id_recenzji = $_POST['id_recenzji'];
        $query = "DELETE FROM Recenzje WHERE ID_Recenzji = ?";
        $params = [$id_recenzji];
        $stmt = sqlsrv_query($conn, $query, $params);
    
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $success_message = "Recenzja została pomyślnie usunięta.";
    }
}

// Pobieranie wszystkich recenzji z bazy danych
$query = "SELECT * FROM Recenzje";
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
    <title>Admin - Recenzje</title>
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

        <!-- Tabela recenzji -->
        <table>
            <thead>
                <tr>
                    <th>ID Recenzji</th>
                    <th>ID Produktu</th>
                    <th>ID Użytkownika</th>
                    <th>Ocena</th>
                    <th>Komentarz</th>
                    <th>Data Recenzji</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <form method="post">
                        <td><?php echo $row['ID_Recenzji']; ?></td>
                        <!-- Zmieniamy te pola na zwykły tekst -->
                        <td><?php echo $row['ID_Produktu']; ?></td>
                        <td><?php echo $row['ID_Uzytkownika']; ?></td>
                        <td><input type="number" name="ocena" value="<?php echo $row['Ocena']; ?>" min="1" max="5" required></td>
                        <td><textarea name="komentarz"><?php echo $row['Komentarz']; ?></textarea></td>
                        <td><?php echo $row['Data_Recenzji']->format('Y-m-d H:i:s'); ?></td>
                        <td>
                            <!-- Ukryty input do przekazania ID recenzji -->
                            <input type="hidden" name="id_recenzji" value="<?php echo $row['ID_Recenzji']; ?>">
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


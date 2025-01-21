<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $haslo = password_hash($_POST['haslo'], PASSWORD_DEFAULT); // Hashowanie hasła
        $adres = $_POST['adres'];
        $id_uprawnienia = $_POST['id_uprawnienia'];
        $query = "INSERT INTO Uzytkownik (Imie, Nazwisko, Email, Haslo, Adres, ID_Uprawnienia) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array($imie, $nazwisko, $email, $haslo, $adres, $id_uprawnienia);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $adres = $_POST['adres'];
        $id_uprawnienia = $_POST['id_uprawnienia'];
        $query = "UPDATE Uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ?, ID_Uprawnienia = ? WHERE ID_Uzytkownika = ?";
        $params = array($imie, $nazwisko, $email, $adres, $id_uprawnienia, $id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        // Najpierw usuń powiązane rekordy z tabeli Koszyk
        $query = "DELETE FROM Koszyk WHERE ID_Uzytkownika = ?";
        $params = array($id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        // Następnie usuń użytkownika
        $query = "DELETE FROM Uzytkownik WHERE ID_Uzytkownika = ?";
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// Pobieranie wszystkich użytkowników z bazy danych
$query = "SELECT * FROM Uzytkownik";
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
    <title>Admin - Użytkownicy</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="logo">TECHHOUSE - Panel Admina</div>
    </header>
    <main>
        <h1>Użytkownicy</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imię</th>
                    <th>Nazwisko</th>
                    <th>Email</th>
                    <th>Adres</th>
                    <th>Uprawnienia</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['ID_Uzytkownika']; ?></td>
                    <td><?php echo $row['Imie']; ?></td>
                    <td><?php echo $row['Nazwisko']; ?></td>
                    <td><?php echo $row['Email']; ?></td>
                    <td><?php echo $row['Adres']; ?></td>
                    <td><?php echo $row['ID_Uprawnienia']; ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">
                            <input type="text" name="imie" value="<?php echo $row['Imie']; ?>">
                            <input type="text" name="nazwisko" value="<?php echo $row['Nazwisko']; ?>">
                            <input type="text" name="email" value="<?php echo $row['Email']; ?>">
                            <input type="text" name="adres" value="<?php echo $row['Adres']; ?>">
                            <select name="id_uprawnienia">
                                <option value="1" <?php if ($row['ID_Uprawnienia'] == 1) echo 'selected'; ?>>Klient</option>
                                <option value="2" <?php if ($row['ID_Uprawnienia'] == 2) echo 'selected'; ?>>Pracownik</option>
                                <option value="3" <?php if ($row['ID_Uprawnienia'] == 3) echo 'selected'; ?>>Admin</option>
                            </select>
                            <button type="submit" name="update">Aktualizuj</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">
                            <button type="submit" name="delete">Usuń</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <h2>Dodaj nowego użytkownika</h2>
        <form method="post">
            <label for="imie">Imię:</label>
            <input type="text" id="imie" name="imie" required>
            
            <label for="nazwisko">Nazwisko:</label>
            <input type="text" id="nazwisko" name="nazwisko" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="adres">Adres:</label>
            <input type="text" id="adres" name="adres" required>
            
            <label for="haslo">Hasło:</label>
            <input type="password" id="haslo" name="haslo" required>
            
            <label for="id_uprawnienia">Uprawnienie:</label>
            <select id="id_uprawnienia" name="id_uprawnienia" required>
                <option value="1">Klient</option>
                <option value="2">Pracownik</option>
                <option value="3">Admin</option>
            </select>
            
            <input type="submit" name="create" value="Dodaj">
        </form>
        <br>
        <a href="admin.php"><button>Powrót do panelu admina</button></a>
    </main>
</body>
</html>
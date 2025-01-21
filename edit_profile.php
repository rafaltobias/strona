<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    header("Location: login.php"); // Jeśli użytkownik nie jest zalogowany, przekierowanie na stronę logowania
    exit();
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Pobranie danych użytkownika z bazy danych
$query = "SELECT Imie, Nazwisko, Email, Adres FROM Uzytkownik WHERE ID_Uzytkownika = ?";
$params = [$id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Aktualizacja danych użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $adres = $_POST['adres'];

    $update_query = "UPDATE Uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ? WHERE ID_Uzytkownika = ?";
    $update_params = [$imie, $nazwisko, $email, $adres, $id_uzytkownika];
    $update_stmt = sqlsrv_query($conn, $update_query, $update_params);

    if ($update_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        header("Location: profil.php"); // Przekierowanie na stronę profilu po pomyślnej aktualizacji
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj Profil - TechHouse</title>
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <header class="header">
        <div class="logo">TechHouse</div>
        <nav class="user-menu">
            <a href="index.php">Strona główna</a>
            <a href="profil.php">Profil</a>
            <a href="logout.php">Wyloguj</a>
        </nav>
    </header>
    <main>
        <h1>Edytuj Profil</h1>
        <form method="post">
            <label for="imie">Imię:</label>
            <input type="text" id="imie" name="imie" value="<?php echo htmlspecialchars($user['Imie']); ?>" required>
            
            <label for="nazwisko">Nazwisko:</label>
            <input type="text" id="nazwisko" name="nazwisko" value="<?php echo htmlspecialchars($user['Nazwisko']); ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
            
            <label for="adres">Adres:</label>
            <input type="text" id="adres" name="adres" value="<?php echo htmlspecialchars($user['Adres']); ?>" required>
            
            <button type="submit">Zapisz zmiany</button>
        </form>
    </main>
</body>
</html>
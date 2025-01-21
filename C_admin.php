<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $haslo = password_hash($_POST['haslo'], PASSWORD_BCRYPT);
    $adres = $_POST['adres'];
    $id_uprawnienia = $_POST['id_uprawnienia'];

    $sql = "INSERT INTO Uzytkownik (Imie, Nazwisko, Email, Haslo, Adres, ID_Uprawnienia) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $params = [$imie, $nazwisko, $email, $haslo, $adres, $id_uprawnienia];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Użytkownika</title>
    <link rel="stylesheet" href="css/global.css">
</head>
<body>
    <div class="container">
        <h1>Dodaj Nowego Użytkownika</h1>
        <<form action="create.php" method="POST">
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
    
    <input type="submit" value="Dodaj">
</form>
    </div>
    <br>
    <a href="admin.php"><button>Powrót do panelu admina</button></a>
</body>
</html>
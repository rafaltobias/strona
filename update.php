<?php
require 'config.php';
session_start();

if ($_SESSION['user_role'] != 1) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$sql = "SELECT * FROM Uzytkownik WHERE ID_Uzytkownika = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = $_POST['imie'];
    $nazwisko = $_POST['nazwisko'];
    $email = $_POST['email'];
    $adres = $_POST['adres'];
    $id_uprawnienia = $_POST['id_uprawnienia'];
    $haslo = $_POST['haslo'];

    if (!empty($haslo)) {
        $haslo_hash = password_hash($haslo, PASSWORD_BCRYPT);
    } else {
        $haslo_hash = $user['Haslo']; 
    }

    $sql = "UPDATE uzytkownik 
            SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ?, ID_Uprawnienia = ?, Haslo = ? 
            WHERE ID_Uzytkownika = ?";
    $params = [$imie, $nazwisko, $email, $adres, $id_uprawnienia, $haslo_hash, $id];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    header("Location: panel.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj użytkownika</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
        }

        input[type="text"], input[type="email"], input[type="number"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        button {
            padding: 12px 20px;
            background-color: #2ecc71; 
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #27ae60; 
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 20px;
                width: 90%;
            }

            h1 {
                font-size: 20px;
            }

            button {
                font-size: 14px;
                padding: 10px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Edytuj użytkownika</h1>
        <form method="post">
            <label>Imię: <input type="text" name="imie" value="<?= htmlspecialchars($user['Imie']) ?>" required></label><br>
            <label>Nazwisko: <input type="text" name="nazwisko" value="<?= htmlspecialchars($user['Nazwisko']) ?>" required></label><br>
            <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required></label><br>
            <label>Adres: <input type="text" name="adres" value="<?= htmlspecialchars($user['Adres']) ?>" required></label><br>
            <label>ID Uprawnienia: 
                <input type="number" name="id_uprawnienia" value="<?= htmlspecialchars($user['ID_Uprawnienia']) ?>" max="3" required>
            </label><br>
            <label>Nowe hasło (pozostaw puste, jeśli nie chcesz zmieniać): <input type="password" name="haslo"></label><br>
            <button type="submit">Zapisz</button>
        </form>
    </div>
</body>
</html>

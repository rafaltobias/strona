<?php
session_start();
include 'db.php'; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['Email'] ?? '';
    $haslo = $_POST['Haslo'] ?? '';

 
    if (!$email || !$haslo) {
        echo "<p class='error'>Wszystkie pola muszą być wypełnione!</p>";
    } else {
      
        $query = "SELECT * FROM Uzytkownik WHERE Email = ? AND Haslo = HASHBYTES('SHA2_256', ?)";
        $stmt = sqlsrv_prepare($conn, $query, [$email, $haslo]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_execute($stmt);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($user) {
            $_SESSION['ID_Uzytkownika'] = $user['ID_Uzytkownika'];
            $_SESSION['Email'] = $user['Email'];
            $_SESSION['Imie'] = $user['Imie'];
            $_SESSION['Nazwisko'] = $user['Nazwisko'];
            $_SESSION['Adres'] = $user['Adres'];
            $_SESSION['ID_Uprawnienia'] = $user['ID_Uprawnienia'];

        
            header("Location: index.php");
            exit;
        } else {
            echo "<p class='error'>Niepoprawne dane logowania!</p>";
        }

        sqlsrv_free_stmt($stmt);
    }
}

sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        .login-container h1 {
            margin-bottom: 20px;
            color: #008000;
        }
        .login-container label {
            display: block;
            margin: 10px 0 5px;
            text-align: left;
        }
        .login-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #008000;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #006400;
        }
        .register-link {
            display: block;
            margin-top: 15px;
            color: #008000;
            text-decoration: none;
            font-weight: bold;
        }
        .register-link:hover {
            text-decoration: underline;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Logowanie</h1>
        <form method="POST" action="">
            <label for="Email">Email:</label>
            <input type="email" id="Email" name="Email" required>

            <label for="Haslo">Hasło:</label>
            <input type="password" id="Haslo" name="Haslo" required>

            <button type="submit">Zaloguj się</button>
        </form>
        <a href="register.php" class="register-link">Nie masz konta? Zarejestruj się</a>
    </div>
</body>
</html>

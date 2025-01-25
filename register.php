<?php
session_start();
include 'db.php'; 

// Sprawdzanie, czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = $_POST['Imie'] ?? '';
    $nazwisko = $_POST['Nazwisko'] ?? '';
    $email = $_POST['Email'] ?? '';
    $haslo = $_POST['Haslo'] ?? '';
    $adres = $_POST['Adres'] ?? '';

    // Walidacja formularza
    if (!$imie || !$nazwisko || !$email || !$haslo || !$adres) {
        echo "Wszystkie pola muszą być wypełnione!";

    } else {
        // Sprawdzanie, czy użytkownik o podanym emailu już istnieje
        $checkEmailQuery = "SELECT * FROM Uzytkownik WHERE Email = ?";
        $checkEmailStmt = sqlsrv_prepare($conn, $checkEmailQuery, [$email]);

        if ($checkEmailStmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_execute($checkEmailStmt);
        $existingUser = sqlsrv_fetch_array($checkEmailStmt, SQLSRV_FETCH_ASSOC);

        if ($existingUser) {
            echo "Użytkownik z takim adresem email już istnieje!";
        } else {
            // Hashowanie hasła w zapytaniu SQL
            $query = "
                INSERT INTO Uzytkownik (Imie, Nazwisko, Email, Haslo, Adres, ID_Uprawnienia)
                VALUES (?, ?, ?, HASHBYTES('SHA2_256', ?), ?, ?)";
            $params = [$imie, $nazwisko, $email, $haslo, $adres, 1]; // Domyślnie uprawnienie = 1

            $stmt = sqlsrv_prepare($conn, $query, $params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            if (sqlsrv_execute($stmt)) {
                // Ustawienie sesji użytkownika
                $_SESSION['user'] = [
                    'Imie' => $imie,
                    'Nazwisko' => $nazwisko,
                    'Email' => $email,
                    'ID_Uprawnienia' => 1
                ];

                // Przekierowanie na stronę główną
                header("Location: index.php");
                exit();
            } else {
                echo "Błąd podczas rejestracji.";
            }

            sqlsrv_free_stmt($stmt);
        }

        sqlsrv_free_stmt($checkEmailStmt);
    }
}

// Zamknięcie połączenia z bazą danych
sqlsrv_close($conn);
?>


<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rejestracja</title>
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
    .register-container {
      background-color: #ffffff;
      color: #333;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      text-align: center;
      width: 100%;
      max-width: 400px;
    }
    .register-container h1 {
      margin-bottom: 20px;
      color: #008000;
    }
    .register-container label {
      display: block;
      margin: 10px 0 5px;
      text-align: left;
    }
    .register-container input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .register-container button {
      width: 100%;
      padding: 10px;
      background-color: #008000;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }
    .register-container button:hover {
      background-color: #006400;
    }
    .login-link {
      display: block;
      margin-top: 15px;
      color: #008000;
      text-decoration: none;
      font-weight: bold;
    }
    .login-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="register-container">
    <h1>Rejestracja</h1>
    <form method="POST" action="">
      <label for="Imie">Imię:</label>
      <input type="text" id="Imie" name="Imie" required>

      <label for="Nazwisko">Nazwisko:</label>
      <input type="text" id="Nazwisko" name="Nazwisko" required>

      <label for="Email">Email:</label>
      <input type="email" id="Email" name="Email" required>

      <label for="Haslo">Hasło:</label>
      <input type="password" id="Haslo" name="Haslo" required>

      <label for="Adres">Adres:</label>
      <input type="text" id="Adres" name="Adres" required>

      <button type="submit">Zarejestruj się</button>
    </form>
    <a href="login.php" class="login-link">Masz już konto? Zaloguj się</a>
  </div>
</body>
</html>

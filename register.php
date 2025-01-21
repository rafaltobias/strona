<?php
session_start();
include 'db.php'; // Plik z połączeniem do bazy danych

// Sprawdzanie, czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobieranie danych z formularza
    $imie = $_POST['Imie'] ?? '';
    $nazwisko = $_POST['Nazwisko'] ?? '';
    $email = $_POST['Email'] ?? '';
    $haslo = $_POST['Haslo'] ?? '';
    $adres = $_POST['Adres'] ?? '';
    $id_uprawnienia = 1; // Zakładam, że nowy użytkownik ma uprawnienie 1 (standardowe)

    // Walidacja formularza
    if (!$imie || !$nazwisko || !$email || !$haslo || !$adres) {
        echo "Wszystkie pola muszą być wypełnione!";
    } else {
        // Sprawdzanie, czy użytkownik o danym emailu już istnieje
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
            // Hashowanie hasła
            $hashedPassword = password_hash($haslo, PASSWORD_DEFAULT);

            // Dodanie nowego użytkownika do bazy
            $query = "INSERT INTO Uzytkownik (Imie, Nazwisko, Email, Haslo, Adres, ID_Uprawnienia) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$imie, $nazwisko, $email, $hashedPassword, $adres, $id_uprawnienia];

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
                    'ID_Uprawnienia' => $id_uprawnienia
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
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header>
    <h1>Rejestracja</h1>
  </header>

  <main>
    <form method="POST" action="">
      <label for="Imie">Imię:</label>
      <input type="text" name="Imie" required>

      <label for="Nazwisko">Nazwisko:</label>
      <input type="text" name="Nazwisko" required>

      <label for="Email">Email:</label>
      <input type="email" name="Email" required>

      <label for="Haslo">Hasło:</label>
      <input type="password" name="Haslo" required>

      <label for="Adres">Adres:</label>
      <input type="text" name="Adres" required>

      <button type="submit">Zarejestruj się</button>
    </form>
  </main>
</body>
</html>

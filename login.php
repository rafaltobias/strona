<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzanie, czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobieranie danych z formularza
    $email = $_POST['Email'] ?? '';
    $haslo = $_POST['Haslo'] ?? '';

    // Walidacja formularza
    if (!$email || !$haslo) {
        echo "Wszystkie pola muszą być wypełnione!";
    } else {
        // Sprawdzanie, czy użytkownik o danym emailu istnieje
        $query = "SELECT * FROM Uzytkownik WHERE Email = ?";
        $stmt = sqlsrv_prepare($conn, $query, [$email]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_execute($stmt);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($user) {
            // Sprawdzanie, czy podane hasło pasuje do hasła przechowywanego w bazie
            if (password_verify($haslo, $user['Haslo'])) {
                // Użytkownik zalogowany - zapisujemy dane w sesji
                $_SESSION['ID_Uzytkownika'] = $user['ID_Uzytkownika'];
                $_SESSION['Email'] = $user['Email'];
                $_SESSION['Imie'] = $user['Imie'];
                $_SESSION['Nazwisko'] = $user['Nazwisko'];
                $_SESSION['Adres'] = $user['Adres'];
                $_SESSION['ID_Uprawnienia'] = $user['ID_Uprawnienia'];

                // Przekierowanie na stronę po zalogowaniu
                header("Location: index.php");
                exit;
            } else {
                echo "Niepoprawne hasło!";
            }
        } else {
            echo "Użytkownik o podanym emailu nie istnieje!";
        }

        sqlsrv_free_stmt($stmt);
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
    <title>Logowanie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Logowanie</h1>
    </header>

    <main>
        <form method="POST" action="">
            <label for="Email">Email:</label>
            <input type="email" name="Email" required>

            <label for="Haslo">Hasło:</label>
            <input type="password" name="Haslo" required>

            <button type="submit">Zaloguj się</button>
        </form>
    </main>
</body>
</html>

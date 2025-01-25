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

// Obsługa aktualizacji danych użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $imie = $_POST['imie'] ?? '';
    $nazwisko = $_POST['nazwisko'] ?? '';
    $email = $_POST['email'] ?? '';
    $adres = $_POST['adres'] ?? '';

    if ($imie && $nazwisko && $email && $adres) {
        $updateQuery = "UPDATE Uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ? WHERE ID_Uzytkownika = ?";
        $updateParams = [$imie, $nazwisko, $email, $adres, $id_uzytkownika];
        $updateStmt = sqlsrv_query($conn, $updateQuery, $updateParams);

        if ($updateStmt === false) {
            echo "Błąd podczas aktualizacji danych!";
        } else {
            echo "Dane zostały zaktualizowane!";
            $user['Imie'] = $imie;
            $user['Nazwisko'] = $nazwisko;
            $user['Email'] = $email;
            $user['Adres'] = $adres;
        }
    } else {
        echo "Wszystkie pola muszą być wypełnione!";
    }
}

// Obsługa zmiany hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $nowe_haslo = $_POST['nowe_haslo'] ?? '';
    $potwierdz_haslo = $_POST['potwierdz_haslo'] ?? '';

    if ($nowe_haslo && $potwierdz_haslo && $nowe_haslo === $potwierdz_haslo) {
        $passwordQuery = "UPDATE Uzytkownik SET Haslo = HASHBYTES('SHA2_256', ?) WHERE ID_Uzytkownika = ?";
        $passwordParams = [$nowe_haslo, $id_uzytkownika];
        $passwordStmt = sqlsrv_query($conn, $passwordQuery, $passwordParams);

        if ($passwordStmt === false) {
            echo "Błąd podczas zmiany hasła!";
        } else {
            echo "Hasło zostało pomyślnie zmienione!";
        }
    } else {
        echo "Hasła muszą być takie same i wypełnione!";
    }
}

// Określenie bieżącej podstrony
$section = $_GET['section'] ?? 'profile'; // Domyślna sekcja to "profile"

// Funkcje obsługujące różne sekcje
function render_profile($user)
{
    echo "
    <h2>Twój Profil</h2>
    <p>Imię: " . htmlspecialchars($user['Imie']) . "</p>
    <p>Nazwisko: " . htmlspecialchars($user['Nazwisko']) . "</p>
    <p>Email: " . htmlspecialchars($user['Email']) . "</p>
    <p>Adres: " . htmlspecialchars($user['Adres']) . "</p>";
}

function render_edit_profile($user)
{
    echo "
    <h2>Edytuj Dane Profilu</h2>
    <form method='post'>
        <input type='hidden' name='edit_profile'>
        <label for='imie'>Imię:</label>
        <input type='text' id='imie' name='imie' value='" . htmlspecialchars($user['Imie']) . "' required>

        <label for='nazwisko'>Nazwisko:</label>
        <input type='text' id='nazwisko' name='nazwisko' value='" . htmlspecialchars($user['Nazwisko']) . "' required>

        <label for='email'>Email:</label>
        <input type='email' id='email' name='email' value='" . htmlspecialchars($user['Email']) . "' required>

        <label for='adres'>Adres:</label>
        <input type='text' id='adres' name='adres' value='" . htmlspecialchars($user['Adres']) . "' required>

        <button type='submit'>Zapisz zmiany</button>
    </form>";
}

function render_change_password()
{
    echo "
    <h2>Zmiana Hasła</h2>
    <form method='post'>
        <input type='hidden' name='change_password'>
        <label for='nowe_haslo'>Nowe Hasło:</label>
        <input type='password' id='nowe_haslo' name='nowe_haslo' required>

        <label for='potwierdz_haslo'>Potwierdź Hasło:</label>
        <input type='password' id='potwierdz_haslo' name='potwierdz_haslo' required>

        <button type='submit'>Zmień Hasło</button>
    </form>";
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Użytkownika - TechHouse</title>
    <link rel="stylesheet" href="css/global.css">
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 200px;
            background-color: #f4f4f4;
            padding: 20px;
            border-right: 1px solid #ddd;
            height: 100vh;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            margin-bottom: 5px;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #007bff;
            color: white;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>Menu</h3>
        <a href="?section=profile" class="<?php echo $section === 'profile' ? 'active' : ''; ?>">Profil</a>
        <a href="?section=edit_profile" class="<?php echo $section === 'edit_profile' ? 'active' : ''; ?>">Edycja Danych</a>
        <a href="?section=change_password" class="<?php echo $section === 'change_password' ? 'active' : ''; ?>">Zmiana Hasła</a>
        <a href="logout.php">Wyloguj</a>
    </div>
    <div class="content">
        <?php
        // Wyświetlenie odpowiedniej sekcji na podstawie parametru "section"
        switch ($section) {
            case 'edit_profile':
                render_edit_profile($user);
                break;
            case 'change_password':
                render_change_password();
                break;
            case 'profile':
            default:
                render_profile($user);
                break;
        }
        ?>
    </div>
</body>
</html>

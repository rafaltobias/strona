<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Zmienna dla komunikatów
$message = '';

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
        // Sprawdzenie, czy nowy email już istnieje w bazie
        $checkEmailQuery = "SELECT COUNT(*) AS email_count FROM Uzytkownik WHERE Email = ? AND ID_Uzytkownika != ?";
        $checkEmailParams = [$email, $id_uzytkownika];
        $checkEmailStmt = sqlsrv_query($conn, $checkEmailQuery, $checkEmailParams);

        if ($checkEmailStmt === false) {
            $message = "<div class='alert alert-danger'>Błąd podczas sprawdzania emaila!</div>";
        } else {
            $checkEmailResult = sqlsrv_fetch_array($checkEmailStmt, SQLSRV_FETCH_ASSOC);
            if ($checkEmailResult['email_count'] > 0) {
                $message = "<div class='alert alert-danger'>Email jest już używany przez innego użytkownika!</div>";
            } else {
                // Aktualizacja danych użytkownika
                $updateQuery = "UPDATE Uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ? WHERE ID_Uzytkownika = ?";
                $updateParams = [$imie, $nazwisko, $email, $adres, $id_uzytkownika];
                $updateStmt = sqlsrv_query($conn, $updateQuery, $updateParams);

                if ($updateStmt === false) {
                    $message = "<div class='alert alert-danger'>Błąd podczas aktualizacji danych!</div>";
                } else {
                    $message = "<div class='alert alert-success'>Dane zostały zaktualizowane!</div>";
                    $user['Imie'] = $imie;
                    $user['Nazwisko'] = $nazwisko;
                    $user['Email'] = $email;
                    $user['Adres'] = $adres;
                }
            }
        }
    } else {
        $message = "<div class='alert alert-warning'>Wszystkie pola muszą być wypełnione!</div>";
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Stylizacja powiadomień */
        .alert {
            margin-top: 20px;
            font-size: 16px;
            border-radius: 5px;
        }

        /* Stylizacja całej strony */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        /* Nagłówek */
        .header {
            background-color: #008000;
            color: white;
            padding: 10px 20px;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .header .logo {
            font-size: 24px;
            font-weight: bold;
        }

        .header .user-menu {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
        }

        .header .user-menu a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }

        .header .user-menu a:hover {
            text-decoration: underline;
        }

        .header .logo a {
            text-decoration: none;
            color: white;
        }

        /* Kontener główny */
        .main-container {
            display: flex;
            margin-top: 70px; /* Odstęp po nagłówku */
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #f4f4f4;
            padding: 20px;
            border-right: 1px solid #ddd;
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
            background-color: #008000;
            color: white;
        }

        /* Zawartość */
        .content {
            flex: 1;
            padding: 20px;
            background-color: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
        }

        form {
            margin-top: 20px;
        }

        form input, form button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form button {
            background-color: #008000;
            color: white;
            cursor: pointer;
        }

        form button:hover {
            background-color: #005700;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <a href="#">TechHouse</a>
        </div>
        <div class="user-menu">
            <a href="?section=profile">Twój Profil</a>
            <a href="?section=edit_profile">Edytuj Profil</a>
            <a href="?section=change_password">Zmień Hasło</a>
            <a href="logout.php">Wyloguj</a>
        </div>
    </div>

    <div class="main-container">
        <div class="sidebar">
            <a href="?section=profile" class="<?php echo ($section === 'profile') ? 'active' : ''; ?>">Profil</a>
            <a href="?section=edit_profile" class="<?php echo ($section === 'edit_profile') ? 'active' : ''; ?>">Edytuj Profil</a>
            <a href="?section=change_password" class="<?php echo ($section === 'change_password') ? 'active' : ''; ?>">Zmień Hasło</a>
        </div>
        <div class="content">
            <?php if ($message) echo $message; // Wyświetlanie komunikatów ?>
            <?php
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
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

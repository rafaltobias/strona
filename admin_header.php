<?php
// Sprawdzamy, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admina - TECHHOUSE</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Styl dla nagłówka */
        header {
            background-color: #333;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            font-family: Arial, sans-serif;
        }

        /* Logo */
        .logo {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Nawigacja */
        nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
        }

        nav ul li {
            margin-right: 20px;
        }

        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #575757;
        }

        /* Informacje o użytkowniku */
        .user-info {
            font-size: 16px;
        }

        .user-info span {
            margin-right: 15px;
        }

        /* Dodaj tło i popraw wygląd przycisku wylogowania */
        .user-info a {
            color: #ff6347;
            text-decoration: none;
            font-weight: bold;
        }

        .user-info a:hover {
            text-decoration: underline;
        }

        /* Zapewniamy responsywność */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            nav ul {
                flex-direction: column;
                width: 100%;
                margin-top: 10px;
            }

            nav ul li {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">TECHHOUSE - Panel Admina</div>
        
        <nav>
            <ul>
                <li><a href="index.php">Strona Główna</a></li>
                <li><a href="admin.php">Panel Główny</a></li>
                <li><a href="users.php">Użytkownicy</a></li>
                <li><a href="products.php">Produkty</a></li>

                <!-- Jeśli użytkownik ma uprawnienia admina, pokaż dodatkowe opcje -->
                <?php if ($_SESSION['ID_Uprawnienia'] == 3): ?>
                    <li><a href="admin_settings.php">Ustawienia Admina</a></li>
                <?php endif; ?>
                
                <!-- Wylogowanie -->
                <li><a href="logout.php">Wyloguj się</a></li>
            </ul>
        </nav>
        
        <div class="user-info">
            <!-- Wyświetl informacje o zalogowanym użytkowniku -->
            <span>Witaj, <?php echo $_SESSION['Imie']; ?></span> | 
            <span>Rola: <?php echo ($_SESSION['ID_Uprawnienia'] == 3) ? 'Administrator' : 'Pracownik'; ?></span>
        </div>
    </header>
    
</body>
</html>

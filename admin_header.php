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
</head>
<body>
    <header class="header">
        <div class="logo">TECHHOUSE - Panel Admina</div>
        
        <nav>
            <ul>
                <!-- Link do strony głównej panelu admina -->
                <li><a href="admin.php">Panel Główny</a></li>
                
                <!-- Link do zarządzania użytkownikami -->
                <li><a href="users.php">Użytkownicy</a></li>

                <!-- Link do zarządzania produktami -->
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
    <main>

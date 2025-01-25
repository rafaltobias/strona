
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHouse</title>
   <style>
    /* Globalne style */
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
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header .logo {
    font-size: 24px;
    font-weight: bold;
}

/* Menu użytkownika */
.header .user-menu {
    display: flex;
}

.header .user-menu a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    font-size: 16px;
}

.header .user-menu a:hover {
    text-decoration: underline;
}

/* Link do logo - strona główna */
.header .logo a {
    text-decoration: none;
    color: white;
}

/* Dodatkowe style dla pozostałej zawartości strony */
h1 {
    text-align: center;
    margin: 30px 0;
}

   </style>
</head>
<body>

<header class="header">
    <div class="logo">
        <a href="index.php">TechHouse</a> <!-- Link do strony głównej -->
    </div>

    
        <!-- Jeśli nie jesteśmy na stronie głównej, wyświetl menu użytkownika -->
        <nav class="user-menu">
            <a href="index.php">Strona główna</a>
            <a href="logout.php">Wyloguj</a>
            <a href="cart.php">Koszyk</a>
            <a href="zamowienia.php">Zamówienia</a>
            <a href="profil.php">Profil</a>
        </nav>
  
</header>

<!-- Pozostała część strony (zawartość strony docelowej) -->

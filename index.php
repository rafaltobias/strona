<?php
session_start();
include 'db.php'; // Plik z połączeniem do bazy danych

// Sprawdzenie, czy użytkownik jest zalogowany
$is_logged_in = isset($_SESSION['ID_Uzytkownika']);
$user_name = $is_logged_in ? $_SESSION['Imie'] : '';
$ID_Uprawnienia = $is_logged_in ? $_SESSION['ID_Uprawnienia'] : 0;

// Pobieranie dostępnych kategorii z bazy danych
$category_query = "SELECT ID_Kategorii, Nazwa_Kategorii FROM Kategoria_produktow";
$category_result = sqlsrv_query($conn, $category_query);

if ($category_result === false) {
    die(print_r(sqlsrv_errors(), true));
}

$categories = [];
while ($category_row = sqlsrv_fetch_array($category_result, SQLSRV_FETCH_ASSOC)) {
    $categories[] = $category_row;
}

// Pobieranie produktów z bazy danych
$search_query = '';
$category_filter = '';
$params = [];

if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $query = "SELECT * FROM Produkt WHERE Nazwa_Produktu LIKE ? AND Aktywny = 1 ORDER BY ID_Produktu";
    $params[] = "%$search_query%";
} elseif (isset($_GET['category'])) {
    $category_filter = $_GET['category'];
    $query = "SELECT * FROM Produkt WHERE ID_Kategorii = ?  AND Aktywny = 1  ORDER BY ID_Produktu";
    $params[] = $category_filter;
} else {
    $query = "SELECT TOP 3 * FROM Produkt WHERE Aktywny = 1 ORDER BY ID_Produktu"; // Pobierz tylko 3 produkty
}

$result = sqlsrv_query($conn, $query, $params);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechHouse</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background-color: #008000;
      color: white;
    }
    .logo {
      font-size: 24px;
      font-weight: bold;
    }
    .search-bar {
      display: flex;
      align-items: center;
    }
    .search-bar input {
      padding: 5px;
      font-size: 16px;
    }
    .search-bar button {
      padding: 5px 10px;
      font-size: 16px;
      background-color: white;
      border: none;
      cursor: pointer;
    }
    .user-menu {
      display: flex;
      align-items: center;
    }
    .user-menu p {
      margin: 0 10px 0 0;
    }
    .user-menu a {
      margin-left: 10px;
      color: white;
      text-decoration: none;
    }
    .user-menu a:hover {
      text-decoration: underline;
    }
    .admin-panel {
      margin-left: 10px;
    }
    .product-list {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin: 20px 0;
    }
    .product {
      background-color: white;
      padding: 15px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      flex: 1 1 calc(33.333% - 20px);
    }
    .product img {
      max-width: 100%;
      height: auto;
    }
    .product h3 {
      font-size: 18px;
      margin: 10px 0;
    }
    .product p {
      margin: 5px 0;
      color: #333;
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="logo">TECHHOUSE</div>
    <div class="search-bar">
      <form method="GET" action="">
        <input type="text" name="search" placeholder="Wyszukaj w sklepie" value="<?= htmlspecialchars($search_query) ?>">
        <button type="submit">Szukaj</button>
      </form>
    </div>
    <div class="user-menu">
      <?php if ($is_logged_in): ?>
        <p>Witaj, <?= htmlspecialchars($user_name) ?>!</p>
        <a href="logout.php">Wyloguj</a>
        <a href="cart.php">Koszyk</a>
        <a href="zamowienia.php">Zamówienia</a>
        <a href="profil.php">Profil</a>
        <?php if ($ID_Uprawnienia == 3): ?>
          <div class="admin-panel">
            <a href="admin.php">Panel Admina</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <a href="register.php">Rejestracja</a>
        <a href="login.php">Logowanie</a>
      <?php endif; ?>
    </div>
  </header>

  <main>
    <section class="banner">
      <h1>TU IDZIE BANNER</h1>
    </section>

    <section class="content">
      <aside class="sidebar">
        <h2>Produkty</h2>
        <form method="GET" action="">
          <ul>
            <?php foreach ($categories as $category): ?>
              <li>
                <input type="checkbox" name="category" value="<?= $category['ID_Kategorii'] ?>" <?= $category_filter == $category['ID_Kategorii'] ? 'checked' : '' ?>>
                <?= htmlspecialchars($category['Nazwa_Kategorii']) ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <button type="submit">Filtruj</button>
        </form>
      </aside>

      <div class="products">
        <h2>Polecane Produkty</h2>
        <div class="product-list">
          <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
            <div class="product">
              <img src="https://via.placeholder.com/150" alt="Produkt">
              <h3><?= htmlspecialchars($row['Nazwa_Produktu']) ?></h3>
              <p>⭐⭐⭐⭐⭐</p>
              <p><?= number_format($row['Cena'], 2) ?> zł</p>
              <a href="product.php?id=<?= htmlspecialchars($row['ID_Produktu']) ?>">Zobacz produkt</a>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>
  </main>

  <?php 
  // Zwolnienie zasobów
  sqlsrv_free_stmt($result);
  sqlsrv_close($conn);
  ?>
</body>
</html>
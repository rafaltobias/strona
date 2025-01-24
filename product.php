<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Pobieranie ID produktu z parametru URL
$product_id = $_GET['id'] ?? 0;

// Jeśli ID jest nieprawidłowe, wyświetlamy błąd
if ($product_id <= 0) {
    die("Nieprawidłowy ID produktu.");
}

// Pobieranie szczegółów produktu z bazy danych
$query = "SELECT * FROM Produkt WHERE ID_Produktu = ?";
$params = [$product_id];
$stmt = sqlsrv_prepare($conn, $query, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Jeśli produkt nie istnieje lub jest nieaktywny, wyświetlamy modal z błędem
if (!$product || $product['Aktywny'] != 1) {
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Produkt niedostępny</title>
        <link rel="stylesheet" href="css/style.css">
        <style>
            .modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            .modal-content {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                width: 90%;
                max-width: 400px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }
            .modal-content h2 {
                margin-bottom: 15px;
                font-size: 24px;
                color: #333;
            }
            .modal-content p {
                font-size: 16px;
                color: #555;
                margin-bottom: 20px;
            }
            .modal-content a {
                display: inline-block;
                padding: 10px 20px;
                font-size: 16px;
                text-decoration: none;
                background: #007bff;
                color: #fff;
                border-radius: 5px;
                transition: background 0.3s;
            }
            .modal-content a:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="modal">
            <div class="modal-content">
                <h2>Produkt niedostępny</h2>
                <p>Ten produkt jest niedostępny lub został usunięty.</p>
                <a href="index.php">Wróć na stronę główną</a>
            </div>
        </div>
    </body>
    </html>
    HTML;
    exit; // Zatrzymujemy dalsze wykonywanie kodu
}

// Reszta kodu dla aktywnego produktu
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['Nazwa_Produktu']) ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="header">
    <div class="logo">TECHHOUSE</div>
    <div class="user-menu">
      <a href="#">Moje Konto</a>
      <a href="cart.php">Koszyk</a>
    </div>
  </header>

  <main>
    <section class="product-details">
      <h1><?= htmlspecialchars($product['Nazwa_Produktu']) ?></h1>
      <img src="https://via.placeholder.com/300" alt="<?= htmlspecialchars($product['Nazwa_Produktu']) ?>">
      <p><?= number_format($product['Cena'], 2) ?> zł</p>
      <p>⭐⭐⭐⭐⭐</p>
      <p><?= htmlspecialchars($product['Opis']) ?></p>
      <p><strong>Dostępna ilość: <?= $product['Stan_Magazynowy'] ?></strong></p>

      <form action="" method="POST">
        <label for="quantity">Ilość:</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['Stan_Magazynowy'] ?>">
        <button type="submit" name="add_to_cart">Dodaj do koszyka</button>
      </form>
    </section>
  </main>

  <?php 
  // Zwolnienie zasobów
  sqlsrv_free_stmt($stmt);
  sqlsrv_close($conn);
  ?>
</body>
</html>

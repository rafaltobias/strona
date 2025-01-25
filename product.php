<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Pobieranie ID produktu z parametru URL
$product_id = $_GET['id'] ?? 0;

// Jeśli ID jest nieprawidłowe, wyświetlamy błąd
if ($product_id <= 0) {
    echo "<script>alert('Nieprawidłowy ID produktu.');</script>";
    exit;
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

// Jeśli produkt nie istnieje lub jest nieaktywny, wyświetlamy komunikat
if (!$product || $product['Aktywny'] != 1) {
    echo "<script>alert('Produkt niedostępny.');</script>";
    exit;
}

// Obsługa dodawania do koszyka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $user_id = $_SESSION['ID_Uzytkownika'] ?? null;
    $quantity = (int)$_POST['quantity'] ?? 1;

    if (!$user_id) {
        echo <<<HTML
        <div class="modal">
            <div class="modal-content">
                <h2>Musisz być zalogowany!</h2>
                <p>Aby dodać produkt do koszyka, zaloguj się na swoje konto.</p>
                <a href="login.php" class="modal-button">Przejdź do logowania</a>
            </div>
        </div>
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
            .modal-button {
                display: inline-block;
                padding: 10px 20px;
                font-size: 16px;
                text-decoration: none;
                background: #28a745;
                color: #fff;
                border-radius: 5px;
                transition: background 0.3s;
            }
            .modal-button:hover {
                background: #218838;
            }
        </style>
        HTML;
        exit;
    }

    // Pobranie ID najnowszego koszyka użytkownika
    $query = "SELECT TOP 1 ID_Koszyka FROM Koszyk WHERE ID_Uzytkownika = ? AND Status = 'aktywny' ORDER BY Data_Utworzenia DESC";
    $stmt = sqlsrv_prepare($conn, $query, [$user_id]);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($stmt);
    $cart = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($cart) {
        $cart_id = $cart['ID_Koszyka'];
    } else {
        // Tworzenie nowego koszyka, jeśli nie istnieje
        $query = "INSERT INTO Koszyk (ID_Uzytkownika, Data_Utworzenia, Status) OUTPUT INSERTED.ID_Koszyka VALUES (?, GETDATE(), 'aktywny')";
        $stmt = sqlsrv_prepare($conn, $query, [$user_id]);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_execute($stmt);
        $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $cart_id = $result['ID_Koszyka'];
    }

    // Sprawdzanie obecnej ilości produktu w koszyku
    $query = "SELECT Ilosc FROM Koszyk_Produkt WHERE ID_Koszyka = ? AND ID_Produktu = ?";
    $stmt = sqlsrv_prepare($conn, $query, [$cart_id, $product_id]);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($stmt);
    $cart_product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($cart_product) {
        $current_quantity = $cart_product['Ilosc'];
        $new_quantity = $current_quantity + $quantity;

        if ($new_quantity > $product['Stan_Magazynowy']) {
            echo "<script>alert('Nie możesz dodać więcej produktów niż dostępny stan magazynowy.');</script>";
        } else {
            $query = "UPDATE Koszyk_Produkt SET Ilosc = ? WHERE ID_Koszyka = ? AND ID_Produktu = ?";
            $stmt = sqlsrv_prepare($conn, $query, [$new_quantity, $cart_id, $product_id]);

            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            sqlsrv_execute($stmt);
            echo "<script>alert('Produkt zaktualizowany w koszyku.');</script>";
        }
    } else {
        if ($quantity > $product['Stan_Magazynowy']) {
            echo "<script>alert('Nie możesz dodać więcej produktów niż dostępny stan magazynowy.');</script>";
        } else {
            $query = "INSERT INTO Koszyk_Produkt (ID_Koszyka, ID_Produktu, Ilosc) VALUES (?, ?, ?)";
            $stmt = sqlsrv_prepare($conn, $query, [$cart_id, $product_id, $quantity]);

            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            sqlsrv_execute($stmt);
            echo "<script>alert('Produkt dodany do koszyka.');</script>";
        }
    }
}

// Zwalnianie zasobów w odpowiednim momencie
if (isset($stmt) && is_resource($stmt)) {
  sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);
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

  
</body>
</html>

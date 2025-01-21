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

// Jeśli produkt nie istnieje, wyświetlamy błąd
if (!$product) {
    die("Produkt nie znaleziony.");
}

// Obsługa dodawania do koszyka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $user_id = $_SESSION['ID_Uzytkownika'] ?? null; // Pobierz ID użytkownika z sesji

    if (!$user_id) {
        die("Musisz być zalogowany, aby dodać produkt do koszyka.");
    }

    // Sprawdzenie, czy użytkownik ma już aktywny koszyk
    $cart_query = "SELECT TOP 1 ID_Koszyka FROM Koszyk WHERE ID_Uzytkownika = ? ORDER BY Data_Utworzenia DESC";
    $cart_params = [$user_id];
    $cart_stmt = sqlsrv_prepare($conn, $cart_query, $cart_params);

    if ($cart_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($cart_stmt);
    $cart = sqlsrv_fetch_array($cart_stmt, SQLSRV_FETCH_ASSOC);

    if (!$cart) {
        die("Nie znaleziono aktywnego koszyka.");
    }

    $cart_id = $cart['ID_Koszyka'];

    // Pobierz żądaną ilość produktu
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Sprawdź, czy ilość nie przekracza stanu magazynowego
    if ($quantity > $product['Stan_Magazynowy']) {
        die("Nie możesz dodać więcej produktów niż dostępne w magazynie.");
    }

    // Sprawdzenie, czy produkt już jest w koszyku
    $check_query = "SELECT Ilosc FROM Koszyk_Produkt WHERE ID_Koszyka = ? AND ID_Produktu = ?";
    $check_params = [$cart_id, $product_id];
    $check_stmt = sqlsrv_prepare($conn, $check_query, $check_params);

    if ($check_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_execute($check_stmt);
    $product_in_cart = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);

    if ($product_in_cart) {
        // Jeśli produkt jest już w koszyku, aktualizuj jego ilość
        $new_quantity = $product_in_cart['Ilosc'] + $quantity;

        if ($new_quantity > $product['Stan_Magazynowy']) {
            die("Łączna ilość produktu w koszyku nie może przekroczyć stanu magazynowego.");
        }

        $update_query = "UPDATE Koszyk_Produkt SET Ilosc = ? WHERE ID_Koszyka = ? AND ID_Produktu = ?";
        $update_params = [$new_quantity, $cart_id, $product_id];
        $update_stmt = sqlsrv_prepare($conn, $update_query, $update_params);

        if ($update_stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_execute($update_stmt)) {
            echo "<p>Produkt został zaktualizowany w koszyku!</p>";
        } else {
            die("Nie udało się zaktualizować produktu w koszyku.");
        }
    } else {
        // Jeśli produktu nie ma w koszyku, dodaj nowy rekord
        $insert_query = "INSERT INTO Koszyk_Produkt (ID_Koszyka, ID_Produktu, Ilosc) VALUES (?, ?, ?)";
        $insert_params = [$cart_id, $product_id, $quantity];
        $insert_stmt = sqlsrv_prepare($conn, $insert_query, $insert_params);

        if ($insert_stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_execute($insert_stmt)) {
            echo "<p>Produkt został dodany do koszyka!</p>";
        } else {
            die("Nie udało się dodać produktu do koszyka.");
        }
    }
}
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

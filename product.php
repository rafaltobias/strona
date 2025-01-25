<?php
session_start();
include 'db.php'; // Połączenie z bazą danych


$product_id = $_GET['id'] ?? 0;

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
$notification = ''; // Zmienna przechowująca komunikat dla użytkownika
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
            $notification = 'Nie możesz dodać więcej produktów niż dostępny stan magazynowy.';
        } else {
            $query = "UPDATE Koszyk_Produkt SET Ilosc = ? WHERE ID_Koszyka = ? AND ID_Produktu = ?";
            $stmt = sqlsrv_prepare($conn, $query, [$new_quantity, $cart_id, $product_id]);

            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            sqlsrv_execute($stmt);
            $notification = 'Produkt zaktualizowany w koszyku.';
        }
    } else {
        if ($quantity > $product['Stan_Magazynowy']) {
            $notification = 'Nie możesz dodać więcej produktów niż dostępny stan magazynowy.';
        } else {
            $query = "INSERT INTO Koszyk_Produkt (ID_Koszyka, ID_Produktu, Ilosc) VALUES (?, ?, ?)";
            $stmt = sqlsrv_prepare($conn, $query, [$cart_id, $product_id, $quantity]);

            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            sqlsrv_execute($stmt);
            $notification = 'Produkt dodany do koszyka.';
        }
    }
}

// Obsługa dodawania recenzji
$review_message = ''; // Zmienna przechowująca komunikat dla użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $user_id = $_SESSION['ID_Uzytkownika'] ?? null;
    $rating = (int)$_POST['rating'];
    $review_content = $_POST['review_content'] ?? '';

    if (!$user_id) {
        echo <<<HTML
        <div class="modal">
            <div class="modal-content">
                <h2>Musisz być zalogowany!</h2>
                <p>Aby dodać recenzję, zaloguj się na swoje konto.</p>
                <a href="login.php" class="modal-button">Przejdź do logowania</a>
            </div>
        </div>
        HTML;
        exit;
    }

    if ($rating >= 1 && $rating <= 5 && !empty($review_content)) {
        
      $query = "INSERT INTO Recenzje (ID_Produktu, ID_Uzytkownika, Ocena, Komentarz, Data_Recenzji) VALUES (?, ?, ?, ?, GETDATE())";

        $params = [$product_id, $user_id, $rating, $review_content];
       
       
        $stmt = sqlsrv_prepare($conn, $query, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_execute($stmt);
        $review_message = 'Twoja recenzja została dodana.';
    } else {
        $review_message = 'Ocena musi być w skali 1-5, a treść recenzji nie może być pusta.';
    }
}

// Pobranie istniejących recenzji dla produktu
$query = "SELECT r.Komentarz, r.Ocena, r.Data_Recenzji, u.Imie 
          FROM Recenzje r
          JOIN Uzytkownik u ON r.ID_Uzytkownika = u.ID_Uzytkownika
          WHERE r.ID_Produktu = ?
          ORDER BY r.Data_Recenzji DESC";

$stmt = sqlsrv_prepare($conn, $query, [$product_id]);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$reviews = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $reviews[] = $row;
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
  <link rel="stylesheet" href="css/modal.css">
  <link rel="stylesheet" href="css/cart.css">
 
</head>
<body>

  <?php include "header.php";?>

  <div class="container">
    <!-- Główna sekcja -->
    <section class="main-section">
      <h1><?= htmlspecialchars($product['Nazwa_Produktu']) ?></h1>
      <img src="https://via.placeholder.com/400" alt="<?= htmlspecialchars($product['Nazwa_Produktu']) ?>">
    </section>

    <!-- Węższa sekcja -->
    <section class="side-section">
      <p class="price"><?= number_format($product['Cena'], 2) ?> zł</p>
      <p class="description"><?= htmlspecialchars($product['Opis']) ?></p>
      <p><strong>Dostępna ilość: <?= $product['Stan_Magazynowy'] ?></strong></p>

      <form action="" method="POST">
        <label for="quantity">Ilość:</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['Stan_Magazynowy'] ?>" class="quantity">
        <button type="submit" name="add_to_cart" class="add-to-cart">Dodaj do koszyka</button>
      </form>
    </section>
  </div>

  <!-- Formularz dodawania recenzji -->
  <h3>Dodaj recenzję</h3>
      <?php if ($review_message): ?>
        <p style="color: green;"><?= $review_message ?></p>
      <?php endif; ?>

      <form action="" method="POST">
        <label for="rating">Ocena (1-5):</label>
        <select name="rating" id="rating" required>
          <option value="">Wybierz ocenę</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
        </select>

        <label for="review_content">Treść recenzji:</label>
        <textarea name="review_content" id="review_content" rows="4" required></textarea>

        <button type="submit" name="submit_review">Dodaj recenzję</button>
      </form>

      <!-- Wyświetlanie recenzji -->
      <h4>Opinie użytkowników:</h4>
      <?php if (count($reviews) > 0): ?>
        <ul>
          <?php foreach ($reviews as $review): ?>
            <li>
              <p><strong><?= htmlspecialchars($review['Imie']) ?>:</strong> <?= $review['Ocena'] ?> / 5</p>
              <p><?= htmlspecialchars($review['Komentarz']) ?></p>
              <small>Dodano: <?= $review['Data_Recenzji']->format('Y-m-d H:i:s') ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>Brak recenzji dla tego produktu.</p>
      <?php endif; ?>
    </section>
  </div>

  <!-- Modal z powiadomieniem -->
  <?php if ($notification): ?>
  <div class="modal">
    <div class="modal-content">
      <h2><?= $notification ?></h2>
      <p>Chcesz przejść do koszyka czy kontynuować zakupy?</p>
      <a href="cart.php" class="modal-button">Przejdź do koszyka</a>
      <a href="product.php?id=<?= $product_id ?>" class="modal-button">Kontynuuj zakupy</a>
    </div>
  </div>
  <?php endif; ?>

</body>
</html>

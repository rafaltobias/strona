<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    die("Musisz być zalogowany, aby wyświetlić koszyk.");
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Znalezienie najnowszego koszyka dla użytkownika
$query = "SELECT TOP 1 * FROM Koszyk WHERE ID_Uzytkownika = ? ORDER BY Data_Utworzenia DESC";
$params = [$id_uzytkownika];
$stmt = sqlsrv_prepare($conn, $query, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt);
$koszyk = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Jeśli nie znaleziono koszyka
if (!$koszyk) {
    die("Nie masz aktywnego koszyka.");
}

// Pobranie ID koszyka
$koszyk_id = $koszyk['ID_Koszyka'];

// Obsługa AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = $_POST['product_id'] ?? 0;

    if ($action === 'update_quantity') {
        $quantity = (int)($_POST['quantity'] ?? 1);

        // Sprawdzenie, czy ilość jest większa od 0
        if ($quantity < 1) {
            echo json_encode(['error' => 'Ilość musi być większa od 0']);
            exit;
        }

        // Pobranie stanu magazynowego produktu
        $stock_query = "SELECT Stan_Magazynowy FROM Produkt WHERE ID_Produktu = ?";
        $stock_stmt = sqlsrv_prepare($conn, $stock_query, [$product_id]);
        if ($stock_stmt === false) {
            echo json_encode(['error' => sqlsrv_errors()]);
            exit;
        }

        sqlsrv_execute($stock_stmt);
        $stock_row = sqlsrv_fetch_array($stock_stmt, SQLSRV_FETCH_ASSOC);
        $available_stock = $stock_row['Stan_Magazynowy'];

        // Jeśli zapytana ilość jest większa niż dostępny stan magazynowy, ustaw maksymalną ilość
        if ($quantity > $available_stock) {
            $quantity = $available_stock;
            $message = "Dostępny stan magazynowy wynosi tylko $available_stock. Ilość w koszyku została zaktualizowana.";
        }

        // Aktualizacja ilości produktu w koszyku
        $update_query = "UPDATE Koszyk_Produkt SET Ilosc = ? WHERE ID_Koszyka = ? AND ID_Produktu = ?";
        $update_params = [$quantity, $koszyk_id, $product_id];
        $update_stmt = sqlsrv_prepare($conn, $update_query, $update_params);

        if ($update_stmt === false) {
            echo json_encode(['error' => sqlsrv_errors()]);
            exit;
        }

        sqlsrv_execute($update_stmt);
        echo json_encode([
            'success' => true,
            'message' => $message ?? "Ilość produktu została zaktualizowana."
        ]);
        exit;
    } elseif ($action === 'delete_product') {
        // Usunięcie produktu z koszyka
        $delete_query = "DELETE FROM Koszyk_Produkt WHERE ID_Koszyka = ? AND ID_Produktu = ?";
        $delete_params = [$koszyk_id, $product_id];
        $delete_stmt = sqlsrv_prepare($conn, $delete_query, $delete_params);

        if ($delete_stmt === false) {
            echo json_encode(['error' => sqlsrv_errors()]);
            exit;
        }

        sqlsrv_execute($delete_stmt);
        echo json_encode(['success' => true]);
        exit;
    }
}

// Pobranie produktów w koszyku
$query_products = "SELECT p.ID_Produktu, p.Nazwa_Produktu, p.Cena, kp.Ilosc
                   FROM Koszyk_Produkt kp
                   JOIN Produkt p ON p.ID_Produktu = kp.ID_Produktu
                   WHERE kp.ID_Koszyka = ?";
$stmt_products = sqlsrv_prepare($conn, $query_products, [$koszyk_id]);
if ($stmt_products === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_execute($stmt_products);
$products = [];
$total_amount = 0;

// Pobranie danych produktów
while ($row = sqlsrv_fetch_array($stmt_products, SQLSRV_FETCH_ASSOC)) {
    $products[] = $row;
    $total_amount += $row['Cena'] * $row['Ilosc'];
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twój Koszyk</title>
    <link rel="stylesheet" href="css/koszyk.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header class="header">
        <div class="logo">TechHouse</div>
        <nav class="user-menu">
            <a href="index.php">Strona główna</a>
            <a href="logout.php">Wyloguj</a>
            <a href="cart.php">Koszyk</a>
            <a href="zamowienia.php">Zamówienia</a>
        </nav>
    </header>

    <main>
        <section class="cart-section">
            <h1>Twój Koszyk</h1>
            <p>Status koszyka: <?= htmlspecialchars($koszyk['Status']) ?></p>
            <p>Data utworzenia: <?= $koszyk['Data_Utworzenia']->format('Y-m-d H:i:s') ?></p>

            <h2>Produkty w koszyku</h2>
            <?php if (count($products) > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Nazwa Produktu</th>
                            <th>Cena</th>
                            <th>Ilość</th>
                            <th>Łączna cena</th>
                            <th>Akcja</th>
                        </tr>
                    </thead>
                    <tbody id="cart-table">
                        <?php foreach ($products as $row): ?>
                            <tr data-product-id="<?= $row['ID_Produktu'] ?>">
                                <td><?= htmlspecialchars($row['Nazwa_Produktu']) ?></td>
                                <td><?= number_format($row['Cena'], 2) ?> zł</td>
                                <td>
                                    <input type="number" class="quantity-input" value="<?= $row['Ilosc'] ?>" min="1">
                                </td>
                                <td class="total-price"><?= number_format($row['Cena'] * $row['Ilosc'], 2) ?> zł</td>
                                <td>
                                    <button class="delete-button">Usuń</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Łączna kwota: <span id="total-amount"><?= number_format($total_amount, 2) ?></span> zł</strong></p>

                <!-- Przycisk do finalizacji zamówienia -->
                <form action="finalize_order.php" method="GET">
                    <button type="submit" id="finalize-order-button">Finalizuj zamówienie</button>
                </form>

            <?php else: ?>
                <p>Twój koszyk jest pusty.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 TechHouse. Wszelkie prawa zastrzeżone.</p>
    </footer>

    <script>
        // Funkcja aktualizująca ilość produktów
        $(document).on('change', '.quantity-input', function() {
            const row = $(this).closest('tr');
            const productId = row.data('product-id');
            const quantity = $(this).val();

            $.post('cart.php', {
                action: 'update_quantity',
                product_id: productId,
                quantity: quantity
            }, function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert(result.message || 'Ilość została zaktualizowana.');
                    location.reload(); // Odświeżenie strony
                } else {
                    alert(result.error);
                }
            });
        });

        // Funkcja usuwająca produkt
        $(document).on('click', '.delete-button', function() {
            const row = $(this).closest('tr');
            const productId = row.data('product-id');

            $.post('cart.php', {
                action: 'delete_product',
                product_id: productId
            }, function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    row.remove();
                    location.reload(); // Odświeżenie strony
                } else {
                    alert(result.error);
                }
            });
        });
    </script>
</body>
</html>

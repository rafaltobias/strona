<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['ID_Uzytkownika'])) {
    echo json_encode(['error' => 'Musisz być zalogowany, aby wykonać tę operację.']);
    exit;
}

// Pobranie ID użytkownika
$id_uzytkownika = $_SESSION['ID_Uzytkownika'];

// Sprawdzenie, czy żądanie jest typu POST i zawiera wymaganą akcję
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

    // Znalezienie najnowszego koszyka użytkownika
    $query_koszyk = "SELECT TOP 1 ID_Koszyka FROM Koszyk WHERE ID_Uzytkownika = ? ORDER BY Data_Utworzenia DESC";
    $params_koszyk = [$id_uzytkownika];
    $stmt_koszyk = sqlsrv_prepare($conn, $query_koszyk, $params_koszyk);
    
    if ($stmt_koszyk === false) {
        echo json_encode(['error' => sqlsrv_errors()]);
        exit;
    }

    sqlsrv_execute($stmt_koszyk);
    $koszyk = sqlsrv_fetch_array($stmt_koszyk, SQLSRV_FETCH_ASSOC);

    if (!$koszyk) {
        echo json_encode(['error' => 'Nie znaleziono aktywnego koszyka.']);
        exit;
    }

    $koszyk_id = $koszyk['ID_Koszyka'];

    if ($action === 'update_quantity') {
        // Aktualizacja ilości produktu
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

        if ($quantity < 1) {
            echo json_encode(['error' => 'Ilość musi być większa od 0.']);
            exit;
        }

        $update_query = "UPDATE Koszyk_Produkt SET Ilosc = ? WHERE ID_Koszyka = ? AND ID_Produktu = ?";
        $update_params = [$quantity, $koszyk_id, $product_id];
        $stmt_update = sqlsrv_prepare($conn, $update_query, $update_params);

        if ($stmt_update === false) {
            echo json_encode(['error' => sqlsrv_errors()]);
            exit;
        }

        sqlsrv_execute($stmt_update);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'delete_product') {
        // Usunięcie produktu z koszyka
        $delete_query = "DELETE FROM Koszyk_Produkt WHERE ID_Koszyka = ? AND ID_Produktu = ?";
        $delete_params = [$koszyk_id, $product_id];
        $stmt_delete = sqlsrv_prepare($conn, $delete_query, $delete_params);

        if ($stmt_delete === false) {
            echo json_encode(['error' => sqlsrv_errors()]);
            exit;
        }

        sqlsrv_execute($stmt_delete);
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['error' => 'Nieprawidłowa akcja.']);
        exit;
    }
} else {
    echo json_encode(['error' => 'Nieprawidłowe żądanie.']);
    exit;
}
?>

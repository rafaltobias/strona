<?php
session_start();
include 'db.php';

// Sprawdzenie tabeli i ID
$table = isset($_GET['table']) ? $_GET['table'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';
if (!$table || !$id) {
    die("Nieprawidłowe parametry!");
}

// Dopasowanie pola ID na podstawie tabeli
switch ($table) {
    case 'Produkt':
        $id_field = 'ID_Produktu';
        break;
    case 'Klient':
        $id_field = 'ID_Klienta';
        break;
    case 'Koszyk':
        $id_field = 'ID_Koszyka';
        break;
    case 'Zamowienie':
        $id_field = 'ID_Zamowienia';
        break;
    default:
        die("Nieznana tabela: " . htmlspecialchars($table));
}

// Pobranie danych do edycji
$sql = "SELECT * FROM $table WHERE $id_field = ?";
$params = [$id];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
    die("Nie znaleziono rekordu!");
}

sqlsrv_free_stmt($stmt);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj rekord</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin-top: 20px; }
        input, button { padding: 10px; margin: 5px; font-size: 16px; }
        button { background-color: #007BFF; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Edytuj rekord w tabeli <?= htmlspecialchars($table) ?></h1>
    <form action="action.php?table=<?= htmlspecialchars($table) ?>&action=update" method="POST">
        <?php foreach ($row as $key => $value): ?>
            <label for="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($key) ?></label>
            <input type="text" name="<?= htmlspecialchars($key) ?>" id="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>" <?= $key === $id_field ? 'readonly' : '' ?>>
        <?php endforeach; ?>
        <button type="submit">Zapisz zmiany</button>
    </form>
</body>
</html>

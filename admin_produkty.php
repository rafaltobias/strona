<?php
session_start();
include 'db.php';

// Obsługa dodawania produktu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    
    $nazwa_produktu = $_POST['Nazwa_Produktu'] ?? '';
    $cena = $_POST['Cena'] ?? '';
    $stan_magazynowy = $_POST['Stan_Magazynowy'] ?? '';
    $id_kategorii = $_POST['ID_Kategorii'] ?? '';

    // Walidacja danych
    if (!$nazwa_produktu || !$cena || !$stan_magazynowy || !$id_kategorii) {
        echo "Wszystkie pola muszą być wypełnione!";
    } else {
        // Sprawdzenie, czy produkt o takiej nazwie już istnieje w bazie (ignorując wielkość liter)
        $checkQuery = "SELECT COUNT(*) AS count FROM Produkt WHERE LOWER(Nazwa_Produktu) = LOWER(?)";
        $checkParams = [$nazwa_produktu];

        $checkStmt = sqlsrv_prepare($conn, $checkQuery, $checkParams);
        if ($checkStmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_execute($checkStmt);
        $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

        if ($row['count'] > 0) {
            echo "Produkt o tej nazwie już istnieje!";
        } else {
            // Dodanie produktu do bazy danych
            $query = "INSERT INTO Produkt (Nazwa_Produktu, Cena, Stan_Magazynowy, ID_Kategorii) 
                      VALUES (?, ?, ?, ?)";
            $params = [$nazwa_produktu, $cena, $stan_magazynowy, $id_kategorii];

            $stmt = sqlsrv_prepare($conn, $query, $params);
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            if (sqlsrv_execute($stmt)) {
                echo "Produkt został dodany!";
            } else {
                echo "Błąd podczas dodawania produktu.";
            }

            sqlsrv_free_stmt($stmt);
        }

        sqlsrv_free_stmt($checkStmt);
    }
}

// Pobranie danych produktów z bazy
$query = "SELECT * FROM Produkt";
$result = sqlsrv_query($conn, $query);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Pobranie danych kategorii z bazy
$categoryQuery = "SELECT ID_Kategorii, Nazwa_Kategorii FROM Kategoria_Produktow";
$categoryResult = sqlsrv_query($conn, $categoryQuery);

if ($categoryResult === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Przygotowanie kategorii do użytku w dropdown
$categories = [];
while ($categoryRow = sqlsrv_fetch_array($categoryResult, SQLSRV_FETCH_ASSOC)) {
    $categories[$categoryRow['ID_Kategorii']] = $categoryRow['Nazwa_Kategorii'];
}

sqlsrv_free_stmt($categoryResult);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - Produkty</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f4f4f4; }
        .form-container { margin-bottom: 20px; }
        .form-container input, select, button {
            padding: 10px; margin: 5px; font-size: 16px; width: calc(100% - 22px);
        }
        .form-container button { background-color: #008000; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Panel Administratora - Produkty</h1>

    <!-- Formularz dodawania produktu -->
    <div class="form-container">
        <form method="POST">
            <input type="text" name="Nazwa_Produktu" placeholder="Nazwa Produktu" required>
            <input type="number" name="Cena" placeholder="Cena" step="0.01" required>
            <input type="number" name="Stan_Magazynowy" placeholder="Stan Magazynowy" required>
            <select name="ID_Kategorii" required>
                <option value="">Wybierz kategorię</option>
                <?php foreach ($categories as $id => $name): ?>
                    <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add">Dodaj Produkt</button>
        </form>
    </div>

    <!-- Tabela produktów -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nazwa Produktu</th>
                <th>Cena</th>
                <th>Stan Magazynowy</th>
                <th>Kategoria</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
            <tr>
                <td><?= $row['ID_Produktu'] ?></td>
                <td><?= $row['Nazwa_Produktu'] ?></td>
                <td><?= $row['Cena'] ?></td>
                <td><?= $row['Stan_Magazynowy'] ?></td>
                <td><?= htmlspecialchars($categories[$row['ID_Kategorii']] ?? 'Brak kategorii') ?></td>
                <td>
                    <a href="edit.php?table=Produkt&id=<?= htmlspecialchars($row['ID_Produktu']) ?>">Edytuj</a>
                    <a href="?delete=<?= htmlspecialchars($row['ID_Produktu']) ?>" onclick="return confirm('Na pewno usunąć?')">Usuń</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php 
    // Zwolnienie zasobów
    sqlsrv_free_stmt($result);
    sqlsrv_close($conn);
    ?>
</body>
<br>
    <a href="admin.php"><button>Powrót do panelu admina</button></a>
</html>

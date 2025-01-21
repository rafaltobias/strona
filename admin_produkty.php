<?php
session_start();
include 'db.php'; // Plik z połączeniem do bazy danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

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

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $nazwa_produktu = $_POST['nazwa_produktu'];
        $cena = $_POST['cena'];
        $stan_magazynowy = $_POST['stan_magazynowy'];
        $id_kategorii = $_POST['id_kategorii'];
        $opis = $_POST['opis'];
        $query = "INSERT INTO Produkt (Nazwa_Produktu, Cena, Stan_Magazynowy, ID_Kategorii, Opis) VALUES (?, ?, ?, ?, ?)";
        $params = array($nazwa_produktu, $cena, $stan_magazynowy, $id_kategorii, $opis);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['update'])) {
        $id_produktu = $_POST['id_produktu'];
        $nazwa_produktu = $_POST['nazwa_produktu'];
        $cena = $_POST['cena'];
        $stan_magazynowy = $_POST['stan_magazynowy'];
        $id_kategorii = $_POST['id_kategorii'];
        $opis = $_POST['opis'];
        $query = "UPDATE Produkt SET Nazwa_Produktu = ?, Cena = ?, Stan_Magazynowy = ?, ID_Kategorii = ?, Opis = ? WHERE ID_Produktu = ?";
        $params = array($nazwa_produktu, $cena, $stan_magazynowy, $id_kategorii, $opis, $id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['delete'])) {
        $id_produktu = $_POST['id_produktu'];
        $query = "DELETE FROM Produkt WHERE ID_Produktu = ?";
        $params = array($id_produktu);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// Pobieranie wszystkich produktów z bazy danych
$query = "SELECT * FROM Produkt";
$result = sqlsrv_query($conn, $query);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Produkty</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="logo">TECHHOUSE - Panel Admina</div>
    </header>
    <main>
        <h1>Produkty</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa Produktu</th>
                    <th>Cena</th>
                    <th>Stan Magazynowy</th>
                    <th>ID Kategorii</th>
                    <th>Opis</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['ID_Produktu']; ?></td>
                    <td><?php echo $row['Nazwa_Produktu']; ?></td>
                    <td><?php echo $row['Cena']; ?></td>
                    <td><?php echo $row['Stan_Magazynowy']; ?></td>
                    <td><?php echo $row['ID_Kategorii']; ?></td>
                    <td><?php echo $row['Opis']; ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_produktu" value="<?php echo $row['ID_Produktu']; ?>">
                            <input type="text" name="nazwa_produktu" value="<?php echo $row['Nazwa_Produktu']; ?>">
                            <input type="text" name="cena" value="<?php echo $row['Cena']; ?>">
                            <input type="text" name="stan_magazynowy" value="<?php echo $row['Stan_Magazynowy']; ?>">
                            <select name="id_kategorii">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['ID_Kategorii']; ?>" <?php if ($row['ID_Kategorii'] == $category['ID_Kategorii']) echo 'selected'; ?>>
                                        <?php echo $category['Nazwa_Kategorii']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="opis" value="<?php echo $row['Opis']; ?>">
                            <button type="submit" name="update">Aktualizuj</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_produktu" value="<?php echo $row['ID_Produktu']; ?>">
                            <button type="submit" name="delete">Usuń</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <h2>Dodaj nowy produkt</h2>
        <form method="post">
            <label for="nazwa_produktu">Nazwa Produktu:</label>
            <input type="text" id="nazwa_produktu" name="nazwa_produktu" required>
            
            <label for="cena">Cena:</label>
            <input type="text" id="cena" name="cena" required>
            
            <label for="stan_magazynowy">Stan Magazynowy:</label>
            <input type="text" id="stan_magazynowy" name="stan_magazynowy" required>
            
            <label for="id_kategorii">ID Kategorii:</label>
            <select id="id_kategorii" name="id_kategorii" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['ID_Kategorii']; ?>">
                        <?php echo $category['Nazwa_Kategorii']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="opis">Opis:</label>
            <input type="text" id="opis" name="opis" required>
            
            <input type="submit" name="create" value="Dodaj">
        </form>
        <br>
        <a href="admin.php"><button>Powrót do panelu admina</button></a>
    </main>
</body>
</html>
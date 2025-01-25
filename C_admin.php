<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Obsługa sortowania, wyszukiwania i filtrowania
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'Imie'; // Domyślna kolumna do sortowania
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC'; // Domyślny porządek (rosnąco)
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$filterPermission = isset($_GET['filter_permission']) ? $_GET['filter_permission'] : '';

function getUsers($conn, $searchQuery, $filterPermission, $sortColumn, $sortOrder) {
    // Budowanie zapytania z uwzględnieniem sortowania, wyszukiwania i filtrowania
    $query = "SELECT * FROM Uzytkownik WHERE (Imie LIKE ? OR Nazwisko LIKE ? OR Email LIKE ?)";

    if ($filterPermission) {
        $query .= " AND ID_Uprawnienia = ?";
        $params = array("%$searchQuery%", "%$searchQuery%", "%$searchQuery%", $filterPermission);
    } else {
        $params = array("%$searchQuery%", "%$searchQuery%", "%$searchQuery%");
    }

    $query .= " ORDER BY $sortColumn $sortOrder";

    // Wykonanie zapytania
    $result = sqlsrv_query($conn, $query, $params);

    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Zwrócenie wyników zapytania
    return $result;
}
$result = getUsers($conn, $searchQuery, $filterPermission, $sortColumn, $sortOrder);
// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sprawdzanie operacji tworzenia nowego użytkownika
    if (isset($_POST['create'])) {
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $haslo = password_hash($_POST['haslo'], PASSWORD_DEFAULT); // Hashowanie hasła
        $adres = $_POST['adres'];
        $id_uprawnienia = $_POST['id_uprawnienia'];
        
        // Wstawienie nowego użytkownika do bazy
        $query = "INSERT INTO Uzytkownik (Imie, Nazwisko, Email, Haslo, Adres, ID_Uprawnienia) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array($imie, $nazwisko, $email, $haslo, $adres, $id_uprawnienia);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $result = getUsers($conn, $searchQuery, $filterPermission, $sortColumn, $sortOrder);
    }

    // Sprawdzanie operacji aktualizacji użytkownika
    elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $adres = $_POST['adres'];
        
        // Aktualizacja danych użytkownika
        $query = "UPDATE Uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ? WHERE ID_Uzytkownika = ?";
        $params = array($imie, $nazwisko, $email, $adres, $id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $result = getUsers($conn, $searchQuery, $filterPermission, $sortColumn, $sortOrder);
    }

    // Sprawdzanie operacji usuwania użytkownika
    elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];

        // Sprawdzanie, czy użytkownik ma przypisane zamówienia
        $query_check = "SELECT COUNT(*) AS OrderCount FROM Zamowienie WHERE ID_Uzytkownika = ?";
        $stmt_check = sqlsrv_query($conn, $query_check, array($id));

        if ($stmt_check === false) {
            // Obsługa błędu zapytania
            die("Błąd podczas sprawdzania zamówień: " . print_r(sqlsrv_errors(), true));
        }

        $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC);

        if ($row && isset($row['OrderCount']) && $row['OrderCount'] > 0) {
            echo '<div class="alert">
                  Nie można usunąć użytkownika, ponieważ ma przypisane zamówienia!
                  <span class="close-btn" onclick="this.parentElement.style.display=\'none\';">&times;</span>
              </div>';
        } else {
            // Jeśli użytkownik nie ma zamówień, wykonujemy operację usunięcia
            // Najpierw usuwamy powiązane rekordy z tabeli Koszyk
            $query_delete_cart = "DELETE FROM Koszyk WHERE ID_Uzytkownika = ?";
            $stmt_delete_cart = sqlsrv_query($conn, $query_delete_cart, array($id));

            if ($stmt_delete_cart === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            // Następnie usuwamy użytkownika
            $query_delete_user = "DELETE FROM Uzytkownik WHERE ID_Uzytkownika = ?";
            $stmt_delete_user = sqlsrv_query($conn, $query_delete_user, array($id));

            if ($stmt_delete_user === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            echo "Użytkownik został usunięty pomyślnie.";
            $result = getUsers($conn, $searchQuery, $filterPermission, $sortColumn, $sortOrder);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Użytkownicy</title>
    <link rel="stylesheet" href="css/admin_panel.css">
</head>
<body>
    <header class="header">
        <?php include "admin_header.php";?>
    </header>
    <main>
        <h1>Użytkownicy</h1>
        
        <!-- Formularz wyszukiwania -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Wyszukaj użytkowników" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Szukaj</button>
        </form>

         <!-- Formularz do dodawania nowego użytkownika -->
         <h2>Dodaj nowego użytkownika</h2>
        <form method="post">
            <input type="text" name="imie" placeholder="Imię" required>
            <input type="text" name="nazwisko" placeholder="Nazwisko" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="haslo" placeholder="Hasło" required>
            <input type="text" name="adres" placeholder="Adres" required>
            <select name="id_uprawnienia" required>
                <option value="1">Klient</option>
                <option value="2">Pracownik</option>
                <option value="3">Admin</option>
            </select>
            <button type="submit" name="create">Dodaj użytkownika</button>
        </form>
        
        <!-- Filtrowanie użytkowników -->
        <form method="GET" class="filter-form">
            <label for="filter_permission">Filtruj po uprawnieniach:</label>
            <select name="filter_permission" id="filter_permission">
                <option value="">Wszystkie</option>
                <option value="1" <?php if ($filterPermission == 1) echo 'selected'; ?>>Klient</option>
                <option value="2" <?php if ($filterPermission == 2) echo 'selected'; ?>>Pracownik</option>
                <option value="3" <?php if ($filterPermission == 3) echo 'selected'; ?>>Admin</option>
            </select>
            <button type="submit">Filtruj</button>
        </form>

        <!-- Sortowanie -->
        <a href="?sort=Imie&order=<?php echo $sortOrder == 'ASC' ? 'DESC' : 'ASC'; ?>">Sortuj po Imieniu</a> | 
        <a href="?sort=Nazwisko&order=<?php echo $sortOrder == 'ASC' ? 'DESC' : 'ASC'; ?>">Sortuj po Nazwisku</a> | 
        <a href="?sort=Email&order=<?php echo $sortOrder == 'ASC' ? 'DESC' : 'ASC'; ?>">Sortuj po Emailu</a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imię</th>
                    <th>Nazwisko</th>
                    <th>Email</th>
                    <th>Adres</th>
                    <th>Uprawnienia</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <form method="post">
                        <td><?php echo $row['ID_Uzytkownika']; ?></td>
                        
                        <!-- Formularz edycji w odpowiednich komórkach -->
                        <td><input type="text" name="imie" value="<?php echo $row['Imie']; ?>" required></td>
                        <td><input type="text" name="nazwisko" value="<?php echo $row['Nazwisko']; ?>" required></td>
                        <td><input type="email" name="email" value="<?php echo $row['Email']; ?>" required></td>
                        <td><input type="text" name="adres" value="<?php echo $row['Adres']; ?>" required></td>
                        
                        <!-- Wyświetlenie uprawnień użytkownika -->
                        <td>
                            <?php 
                                $uprawnienia = $row['ID_Uprawnienia'] == 1 ? 'Klient' : ($row['ID_Uprawnienia'] == 2 ? 'Pracownik' : 'Admin');
                                echo $uprawnienia;
                            ?>
                        </td>

                        <!-- Ukryty input do przekazania ID użytkownika -->
                        <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">

                        <td>
                            <!-- Formularz aktualizacji -->
                            <button type="submit" name="update">Aktualizuj</button>
                            
                            <!-- Formularz usuwania dostępny tylko dla super adminów -->
                            <?php if ($_SESSION['ID_Uprawnienia'] == 3 && $row['ID_Uprawnienia'] < 3): ?>
                                <button type="submit" name="delete">Usuń</button>
                            <?php endif; ?>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

       
    </main>
</body>
</html>


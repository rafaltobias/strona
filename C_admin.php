<?php
session_start();
include 'db.php'; // Połączenie z bazą danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Obsługa sortowania i wyszukiwania
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'Imie'; // Domyślna kolumna do sortowania
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC'; // Domyślny porządek (rosnąco)
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Budowanie zapytania z uwzględnieniem sortowania i wyszukiwania
$query = "SELECT * FROM Uzytkownik WHERE Imie LIKE ? OR Nazwisko LIKE ? OR Email LIKE ? ORDER BY $sortColumn $sortOrder";
$params = array("%$searchQuery%", "%$searchQuery%", "%$searchQuery%");
$result = sqlsrv_query($conn, $query, $params);

if ($result === false) {
    die(print_r(sqlsrv_errors(), true));
}

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
    }

    // Sprawdzanie operacji aktualizacji użytkownika
    elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $adres = $_POST['adres'];
        $id_uprawnienia = $_POST['id_uprawnienia'];
        
        // Sprawdzanie, czy próbujemy zaktualizować rolę admina (to może zrobić tylko super admin)
        if ($id_uprawnienia == 3) {
            // Jeżeli użytkownik nie jest super adminem, zablokuj próbę zmiany roli na admina
            if ($_SESSION['ID_Uprawnienia'] != 3) {
                echo "Nie masz uprawnień do zmiany roli na admina.";
                exit();
            }
        }
        
        // Aktualizacja danych użytkownika
        $query = "UPDATE Uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ?, ID_Uprawnienia = ? WHERE ID_Uzytkownika = ?";
        $params = array($imie, $nazwisko, $email, $adres, $id_uprawnienia, $id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    // Sprawdzanie operacji usuwania użytkownika
    // Sprawdzanie, czy użytkownik chce usunąć innego użytkownika
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <?php include "admin_header.php";?>
       
    </header>
    <main>
        <h1>Użytkownicy</h1>
        
        <!-- Formularz wyszukiwania -->
        <form method="GET">
            <input type="text" name="search" placeholder="Wyszukaj użytkowników" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Szukaj</button>
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
                    <td><?php echo $row['ID_Uzytkownika']; ?></td>
                    <td><?php echo $row['Imie']; ?></td>
                    <td><?php echo $row['Nazwisko']; ?></td>
                    <td><?php echo $row['Email']; ?></td>
                    <td><?php echo $row['Adres']; ?></td>
                    <td><?php echo $row['ID_Uprawnienia']; ?></td>
                    <td>
                        <!-- Tylko super admin może edytować role lub usuwać adminów -->
                        <?php if ($_SESSION['ID_Uprawnienia'] == 3): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">
                                <input type="text" name="imie" value="<?php echo $row['Imie']; ?>">
                                <input type="text" name="nazwisko" value="<?php echo $row['Nazwisko']; ?>">
                                <input type="text" name="email" value="<?php echo $row['Email']; ?>">
                                <input type="text" name="adres" value="<?php echo $row['Adres']; ?>">
                                <select name="id_uprawnienia">
                                    <option value="1" <?php if ($row['ID_Uprawnienia'] == 1) echo 'selected'; ?>>Klient</option>
                                    <option value="2" <?php if ($row['ID_Uprawnienia'] == 2) echo 'selected'; ?>>Pracownik</option>
                                    <option value="3" <?php if ($row['ID_Uprawnienia'] == 3) echo 'selected'; ?>>Admin</option>
                                </select>
                                <button type="submit" name="update">Aktualizuj</button>
                            </form>
                        <?php endif; ?>

                        <!-- Przyciski usuwania dostępne tylko dla super adminów -->
                        <?php if ($_SESSION['ID_Uprawnienia'] == 3 && $row['ID_Uprawnienia'] < 3): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">
                                <button type="submit" name="delete">Usuń</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Dodaj nowego użytkownika</h2>
        <form method="post">
            <label for="imie">Imię:</label>
            <input type="text" id="imie" name="imie" required>
            
            <label for="nazwisko">Nazwisko:</label>
            <input type="text" id="nazwisko" name="nazwisko" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="adres">Adres:</label>
            <input type="text" id="adres" name="adres" required>
            
            <label for="haslo">Hasło:</label>
            <input type="password" id="haslo" name="haslo" required>
            
            <label for="id_uprawnienia">Uprawnienie:</label>
            <select id="id_uprawnienia" name="id_uprawnienia" required>
                <option value="1">Klient</option>
                <option value="2">Pracownik</option>
                <option value="3">Admin</option>
            </select>
            
            <input type="submit" name="create" value="Dodaj">
        </form>
        <br>
        <a href="admin.php"><button>Powrót do panelu admina</button></a>
    </main>
</body>
</html>

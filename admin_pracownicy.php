<?php
session_start();
include 'db.php'; // Plik z połączeniem do bazy danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Operacje CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $haslo = password_hash($_POST['haslo'], PASSWORD_DEFAULT); // Hashowanie hasła
        $adres = $_POST['adres'];
        $query = "INSERT INTO uzytkownik (Imie, Nazwisko, Email, Haslo, Adres, ID_Uprawnienia) VALUES (?, ?, ?, ?, ?, 2)";
        $params = array($imie, $nazwisko, $email, $haslo, $adres);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $imie = $_POST['imie'];
        $nazwisko = $_POST['nazwisko'];
        $email = $_POST['email'];
        $adres = $_POST['adres'];
        $query = "UPDATE uzytkownik SET Imie = ?, Nazwisko = ?, Email = ?, Adres = ? WHERE ID_Uzytkownika = ?";
        $params = array($imie, $nazwisko, $email, $adres, $id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        // Najpierw usuń powiązane rekordy z tabeli Koszyk
        $query = "DELETE FROM Koszyk WHERE ID_Uzytkownika = ?";
        $params = array($id);
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        // Następnie usuń użytkownika
        $query = "DELETE FROM uzytkownik WHERE ID_Uzytkownika = ?";
        $stmt = sqlsrv_query($conn, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
}

// Pobieranie użytkowników z ID_Uprawnienia = 2
$query = "SELECT * FROM uzytkownik WHERE ID_Uprawnienia = 2";
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
  <title>Admin - Pracownicy</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="header">
    <div class="logo">TECHHOUSE - Panel Admina</div>
  </header>
  <main>
    <h1>Pracownicy</h1>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Imię</th>
          <th>Nazwisko</th>
          <th>Email</th>
          <th>Adres</th>
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
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">
              <input type="text" name="imie" value="<?php echo $row['Imie']; ?>">
              <input type="text" name="nazwisko" value="<?php echo $row['Nazwisko']; ?>">
              <input type="text" name="email" value="<?php echo $row['Email']; ?>">
              <input type="text" name="adres" value="<?php echo $row['Adres']; ?>">
              <button type="submit" name="update">Aktualizuj</button>
            </form>
            <form method="post" style="display:inline;">
              <input type="hidden" name="id" value="<?php echo $row['ID_Uzytkownika']; ?>">
              <button type="submit" name="delete">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <h2>Dodaj nowego pracownika</h2>
    <form method="post">
      <input type="text" name="imie" placeholder="Imię" required>
      <input type="text" name="nazwisko" placeholder="Nazwisko" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="haslo" placeholder="Hasło" required>
      <input type="text" name="adres" placeholder="Adres" required>
      <button type="submit" name="create">Dodaj</button>
    </form>
  </main>
  <br>
    <a href="admin.php"><button>Powrót do panelu admina</button></a>
</body>
</html>
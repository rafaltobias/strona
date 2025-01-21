<?php
session_start();
include 'db.php'; // Plik z połączeniem do bazy danych

// Sprawdzenie, czy użytkownik jest zalogowany i ma uprawnienia admina
if (!isset($_SESSION['ID_Uzytkownika']) || $_SESSION['ID_Uprawnienia'] != 3) {
    header('Location: index.php');
    exit();
}

// Pobieranie faktur z bazy danych
$query = "SELECT f.ID_Faktury, f.ID_Zamowienia, f.Data_Wystawienia, f.Kwota, z.ID_Uzytkownika, z.Data_Zlozenia, z.Status, z.Laczna_Kwota
          FROM Faktury f
          JOIN Zamowienie z ON f.ID_Zamowienia = z.ID_Zamowienia";
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
  <title>Admin - Faktury</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="header">
    <div class="logo">TECHHOUSE - Panel Admina</div>
  </header>
  <main>
    <h1>Faktury</h1>
    <table>
      <thead>
        <tr>
          <th>ID Faktury</th>
          <th>ID Zamówienia</th>
          <th>ID Użytkownika</th>
          <th>Data Złożenia Zamówienia</th>
          <th>Status Zamówienia</th>
          <th>Łączna Kwota Zamówienia</th>
          <th>Data Wystawienia Faktury</th>
          <th>Kwota Faktury</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)): ?>
        <tr>
          <td><?php echo $row['ID_Faktury']; ?></td>
          <td><?php echo $row['ID_Zamowienia']; ?></td>
          <td><?php echo $row['ID_Uzytkownika']; ?></td>
          <td><?php echo $row['Data_Zlozenia']->format('Y-m-d'); ?></td>
          <td><?php echo $row['Status']; ?></td>
          <td><?php echo $row['Laczna_Kwota']; ?></td>
          <td><?php echo $row['Data_Wystawienia']->format('Y-m-d'); ?></td>
          <td><?php echo $row['Kwota']; ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <br>
    <a href="admin.php"><button>Powrót do panelu admina</button></a>
  </main>

</body>
</html>
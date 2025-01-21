<?php
require 'config.php';
session_start();

if ($_SESSION['user_role'] != 1) {
    header("Location: index.php");
    exit;
}
$sql = "SELECT * FROM Uzytkownik";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista użytkowników</title>
<style>   
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background-color: #fff;
    padding: 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    width: 100%;
    max-width: 800px;
    text-align: center;
}

h1 {
    font-size: 24px;
    color: #333;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    table-layout: fixed; 
}

table th, table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
    word-wrap: break-word; 
}

table th {
    background-color: #f4f4f9;
}


.add-user-btn {
    padding: 12px 20px;
    background-color: #2ecc71; 
    color: white;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    margin-bottom: 20px;
    display: inline-block;
}

.add-user-btn:hover {
    background-color: #27ae60;
}

.actions {
    display: flex;
    gap: 10px;  
    justify-content: center;  
    flex-wrap: wrap;  
}

.actions a {
    padding: 6px 12px;
    background-color: #3498db; 
    color: white;
    font-size: 14px;
    text-decoration: none;
    border-radius: 5px;
    white-space: nowrap; 
}

.actions a:hover {
    background-color: #2980b9; /* Ciemniejszy niebieski */
}

.actions a.delete {
    background-color: #e74c3c; /* Czerwony kolor */
}

.actions a.delete:hover {
    background-color: #c0392b; /* Ciemniejszy czerwony */
}
</style> 
</head>
<body>
    <div class="container">
        <header>
            <h1>Lista użytkowników</h1>
        </header>

        <a href="create.php" class="add-user-btn">Dodaj użytkownika</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imię</th>
                    <th>Nazwisko</th>
                    <th>Email</th>
                    <th>Adres</th>
                    <th>ID Uprawnienia</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['ID_Uzytkownika']) ?></td>
                        <td><?= htmlspecialchars($user['Imie']) ?></td>
                        <td><?= htmlspecialchars($user['Nazwisko']) ?></td>
                        <td><?= htmlspecialchars($user['Email']) ?></td>
                        <td><?= htmlspecialchars($user['Adres']) ?></td>
                        <td><?= htmlspecialchars($user['ID_Uprawnienia']) ?></td>
                        <td class="actions">
                            <a href="update.php?id=<?= $user['ID_Uzytkownika'] ?>">Edytuj</a>  
                            <a href="delete.php?id=<?= $user['ID_Uzytkownika'] ?>" class="delete" onclick="return confirm('Czy na pewno chcesz usunąć?')">Usuń</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

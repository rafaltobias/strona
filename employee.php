<?php
require 'config.php';
session_start();

if ($_SESSION['user_role'] != 2) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel pracownika</title>
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

.employee-container {
    background-color: #fff;
    padding: 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
    text-align: center;
}

h1 {
    font-size: 24px;
    color: #333;
    margin-bottom: 30px;
}

.employee-actions {
    display: flex;
    justify-content: center;
}

.logout-button {
    padding: 12px 20px;
    background-color: #2980b9;
    color: white;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
}

.logout-button:hover {
    background-color: #1f6697;
}

@media (max-width: 600px) {
    .employee-container {
        padding: 20px;
        width: 90%;
    }

    h1 {
        font-size: 20px;
    }

    .logout-button {
        font-size: 14px;
        padding: 10px 18px;
    }
}

</style></head>
<body>
    <div class="employee-container">
        <h1>Witaj, <?= htmlspecialchars($_SESSION['user_name']) ?> w panelu pracownika</h1>
        <div class="employee-actions">
            <a href="logout.php" class="logout-button">Wyloguj</a>
        </div>
    </div>
</body>
</html>

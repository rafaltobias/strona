<?php
require 'config.php';
session_start();

if ($_SESSION['user_role'] != 1) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administratora</title>
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

        .admin-container {
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

        .admin-actions {
            display: flex;
            justify-content: center;
            gap: 15px; 
        }

        .admin-button, .logout-button {
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .admin-button {
            background-color: #e74c3c;
        }

        .admin-button:hover {
            background-color: #c0392b;
        }

        .logout-button {
            background-color: #2980b9;
        }

        .logout-button:hover {
            background-color: #1f6697;
        }

        @media (max-width: 600px) {
            .admin-container {
                padding: 20px;
                width: 90%;
            }

            h1 {
                font-size: 20px;
            }

            .admin-button, .logout-button {
                font-size: 14px;
                padding: 10px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Witaj, <?= htmlspecialchars($_SESSION['user_name']) ?> w panelu administratora</h1>
        <div class="admin-actions">
            <a href="panel.php" class="admin-button">Zarządzaj Użytkownikami</a>
        </div>
        <a href="logout.php" class="logout-button">Wyloguj</a>
    </div>
</body>
</html>

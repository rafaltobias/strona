<?php
session_start();
// Lista tabel w projekcie
$tabele = [
    "Produkty" => "admin_produkty.php",
    "Zamówienia" => "admin_zamowienia.php",
    "Produkt w zamówieniu" => "admin_zamowienie_produkt.php",
    "Kategorie" => "admin_kategorie.php",
    "Faktury" => "admin_faktury.php",
    "Koszyki" => "admin_koszyk.php",
    "Produkt w koszyku" => "admin_koszyk_product.php",
    "Uzytkownicy" => "C_admin.php",
    "Recenzje" => "admin_reviews.php"
];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin: 10px 0;
        }
        ul li a {
            text-decoration: none;
            color: #007bff;
            font-size: 1.2em;
            padding: 10px 15px;
            display: inline-block;
            border: 1px solid #007bff;
            border-radius: 4px;
            transition: background-color 0.3s, color 0.3s;
        }
        ul li a:hover {
            background-color: #007bff;
            color: #fff;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
        .back-button {
            margin-top: 20px;
        }
        .back-button a {
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-button a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>
        <ul class="table-list">
            <?php foreach ($tabele as $nazwa => $link): ?>
                <li><a href="<?php echo $link; ?>"><?php echo $nazwa; ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="back-button">
            <a href="index.php">Powrót do strony głównej</a>
        </div>
    </div>
</body>
</html>
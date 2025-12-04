<?php
// config/db.php
$host = 'auth-db1657.hstgr.io';
$dbname = 'u506280443_loujoaDB';
$username = 'u506280443_loujoadbUser';
$password = 'P*JKmKU8+y';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de ligação à base de dados. Contacta o administrador.");
}
?>
<?php

// DB config
$host = 'localhost';
$dbname = 'u506280443_loujoaDB';
$username = 'u506280443_loujoadbUser';
$password = 'P*JKmKU8+y';

// Opções de conecxão PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    // Criar conexão PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $options
    );

    // Conexão estabelecida com sucesso
    // echo "Conectado á DB<br>";

} catch (PDOException $e) {
    // Ver erro
    die("<strong>Erro de conexão:</strong> " . $e->getMessage() . "<br><br>" .
        "<strong>Host:</strong> $host<br>" .
        "<strong>Database:</strong> $dbname<br>" .
        "<strong>Username:</strong> $username");

    // die(Erro de conecxão");
}
?>
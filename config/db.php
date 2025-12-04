<?php
/**
 * Configuração da Base de Dados - LouJoa
 */

// Configurações do servidor de produção
$host = 'localhost'; // Tenta localhost primeiro
$dbname = 'u506280443_loujoaDB';
$username = 'u506280443_loujoadbUser';
$password = 'P*JKmKU8+y';

// Opções de conexão PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
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
    echo "✅ Conectado à base de dados com sucesso!<br>"; // Descomentar para testar
    
} catch (PDOException $e) {
    // MODO DEBUG - Ver o erro real
    die("<strong>Erro de conexão:</strong> " . $e->getMessage() . "<br><br>" .
        "<strong>Host:</strong> $host<br>" .
        "<strong>Database:</strong> $dbname<br>" .
        "<strong>Username:</strong> $username");
    
    // Em produção, usa isto:
    // die("Erro de conexão à base de dados. Contacta o administrador.");
}
?>
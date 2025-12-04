<?php
/**
 * Configuração da Base de Dados - LouJoa
 */

// Configurações do servidor de produção
$host = 'srv1657.hstgr.io'; // ou tenta 'localhost' se estiver no mesmo servidor
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
    // echo "Conectado à base de dados com sucesso!"; // Descomentar para testar
    
} catch (PDOException $e) {
    // Em produção, não mostres detalhes do erro
    die("Erro de conexão à base de dados. Contacta o administrador.");
    
    // Para debug (comentar em produção):
    // die("Erro de conexão: " . $e->getMessage());
}
?>
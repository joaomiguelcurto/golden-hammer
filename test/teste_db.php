<?php
require_once 'config/db.php';
echo "Conecxão à base de dados correu bem";

// Testar se as tabelas existem
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<br><br>Tabelas encontradas: " . implode(', ', $tables);
?>
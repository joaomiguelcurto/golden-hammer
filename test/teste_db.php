<?php
require_once 'config/db.php';
echo "Conecxão à base de dados correu bem";

// Testar se as tabelas existem

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=
    , initial-scale=1.0">
    <title>base de dados</title>
</head>
<body>
    <?php 
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<br><br>Tabelas encontradas: " . implode(', ', $tables);
    ?>
</body>
</html>
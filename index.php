<?php
session_start();

include "test/teste_db.php";

if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>LouJoa - Dashboard</title>
</head>

<body>
    <h1>Bem-vindo, <?= htmlspecialchars($_SESSION['nome_utilizador']) ?>!</h1>
    <a href="logout.php">Sair</a>
</body>

</html>
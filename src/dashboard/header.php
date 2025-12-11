<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LouJoa - Leilões em Tempo Real</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">LouJoa Leilões</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li>Olá, <?= htmlspecialchars($_SESSION['nome_utilizador']) ?>!</li>
                        <li><a href="logout.php">Sair</a></li>
                        <?php if($_SESSION['nome_utilizador'] === 'admin'): ?>
                            <li><a href="admin/">Admin</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="registo.php">Registar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container"></main>
<?php
session_start();
require_once 'config/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_utilizador = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome_utilizador && $senha) {
        // Buscar utilizador
        $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE nome_utilizador = ? OR email = ? LIMIT 1");
        $stmt->execute([$nome_utilizador, $nome_utilizador]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['password'])) {
            // Login com sucesso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nome_utilizador'] = $user['nome_utilizador'];
            $_SESSION['logado'] = true;

            header("Location: index.php");
            exit;
        } else {
            $erro = "Utilizador ou senha incorretos!";
        }
    } else {
        $erro = "Preenche todos os campos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LouJoa - Login</title>
    <style>
        body {font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;}
        .box {background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); width: 350px;}
        h2 {text-align: center; color: #1a1a1a; margin-bottom: 30px;}
        input {width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 16px;}
        button {width: 100%; padding: 13px; background: #e67e22; color: white; border: none; border-radius: 8px; font-size: 17px; cursor: pointer;}
        button:hover {background: #d35400;}
        .erro {color: #e74c3c; text-align: center; margin-top: 15px; font-size: 14px;}
        .logo {text-align: center; margin-bottom: 20px; font-size: 28px; color: #e67e22; font-weight: bold;}
    </style>
</head>
<body>
<div class="box">
    <div class="logo">LouJoa</div>
    <h2>Entrar na conta</h2>

    <?php if ($erro): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="usuario" placeholder="Nome de utilizador ou email" required autofocus>
        <input type="password" name="senha" placeholder="Palavra-passe" required>
        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
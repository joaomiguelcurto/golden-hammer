<?php
session_start();
require_once 'config/db.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_utilizador = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if ($nome_utilizador && $email && $senha && $confirmar_senha) {
        if ($senha !== $confirmar_senha) {
            $erro = "As senhas não coincidem!";
        } else {
            // Verificar se o utilizador ou email já existem
            $stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE nome_utilizador = ? OR email = ? LIMIT 1");
            $stmt->execute([$nome_utilizador, $email]);
            
            if ($stmt->fetch()) {
                $erro = "Utilizador ou Email já estão em uso!";
            } else {
                // Criar hash da senha para segurança
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Inserir na base de dados
                $sql = "INSERT INTO utilizadores (nome_utilizador, email, password) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$nome_utilizador, $email, $senha_hash])) {
                    $sucesso = "Conta criada com sucesso! Podes entrar agora.";
                } else {
                    $erro = "Ocorreu um erro ao registar. Tenta novamente.";
                }
            }
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
    <title>LouJoa - Registo</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="box">
        <div class="logo">LouJoa</div>
        <h2>Criar nova conta</h2>

        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="usuario" placeholder="Nome de utilizador" required value="<?= htmlspecialchars($nome_utilizador ?? '') ?>">
            <input type="email" name="email" placeholder="E-mail" required value="<?= htmlspecialchars($email ?? '') ?>">
            <input type="password" name="senha" placeholder="Palavra-passe" required>
            <input type="password" name="confirmar_senha" placeholder="Confirmar palavra-passe" required>
            <button type="submit">Registar</button>
        </form>

        <div class="footer-link">
            Já tens conta? <a href="login.php">Entrar aqui</a>
        </div>
    </div>
</body>
</html>
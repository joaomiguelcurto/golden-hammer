<?php
session_start();
require_once 'config/db.php';
$file_css = 'css/login.css';
$versioncss = filemtime($file_css);

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
    <link rel="stylesheet" href="css/login.css?v=<?= $versioncss ?>">
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

        <div class="footer-link">
            Não tens conta? <a href="register.php">Regista-te aqui</a>
        </div>

    </div>
</body>

</html>
<?php
require_once 'config/db.php';

echo "<h2>🔍 Debug da Base de Dados - LouJoa</h2>";

// 1. Verificar tabelas
echo "<h3>1. Tabelas na base de dados:</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ <strong>PROBLEMA:</strong> Nenhuma tabela encontrada!<br>";
        echo "→ Precisas executar o SQL para criar as tabelas no phpMyAdmin<br><br>";
    } else {
        echo "✅ Tabelas encontradas: <strong>" . implode(', ', $tables) . "</strong><br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Erro ao listar tabelas: " . $e->getMessage() . "<br><br>";
}

// 2. Verificar utilizadores
echo "<h3>2. Utilizadores registados:</h3>";
try {
    $stmt = $pdo->query("SELECT id, nome_utilizador, email, criado_em FROM utilizadores");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "❌ <strong>PROBLEMA:</strong> Nenhum utilizador na base de dados!<br>";
        echo "→ Precisas criar um utilizador de teste<br><br>";
        
        echo "<h4>Execute este SQL no phpMyAdmin:</h4>";
        echo "<pre style='background:#f4f4f4; padding:15px; border-radius:5px;'>";
        echo "INSERT INTO utilizadores (nome_utilizador, email, password) \n";
        echo "VALUES ('admin', 'admin@loujoa.pt', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');\n";
        echo "</pre>";
        echo "<p><strong>Depois usa:</strong><br>";
        echo "Utilizador: <code>admin</code><br>";
        echo "Password: <code>password</code></p>";
        
    } else {
        echo "✅ <strong>" . count($users) . "</strong> utilizador(es) encontrado(s):<br><br>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Nome Utilizador</th><th>Email</th><th>Criado em</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['nome_utilizador']}</strong></td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['criado_em']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
} catch (PDOException $e) {
    echo "❌ Erro ao listar utilizadores: " . $e->getMessage() . "<br>";
    echo "→ Provavelmente a tabela 'utilizadores' não existe<br><br>";
}

// 3. Testar hash de password
echo "<h3>3. Testar verificação de password:</h3>";
$senha_teste = 'password';
$hash_teste = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if (password_verify($senha_teste, $hash_teste)) {
    echo "✅ A função password_verify() está a funcionar corretamente<br>";
    echo "→ Hash: <code>$hash_teste</code><br>";
    echo "→ Password: <code>$senha_teste</code><br><br>";
} else {
    echo "❌ Problema com password_verify()<br><br>";
}

// 4. Criar utilizador de teste (se não existir)
echo "<h3>4. Criar utilizador de teste automaticamente:</h3>";
try {
    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE nome_utilizador = 'admin' LIMIT 1");
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "ℹ️ Utilizador 'admin' já existe<br><br>";
    } else {
        // Criar novo utilizador
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilizadores (nome_utilizador, email, password) VALUES (?, ?, ?)");
        $stmt->execute(['admin', 'admin@loujoa.pt', $hash]);
        
        echo "✅ <strong>Utilizador criado com sucesso!</strong><br>";
        echo "→ Nome: <code>admin</code><br>";
        echo "→ Email: <code>admin@loujoa.pt</code><br>";
        echo "→ Password: <code>password</code><br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Erro ao criar utilizador: " . $e->getMessage() . "<br><br>";
}

echo "<hr>";
echo "<h3>🎯 Próximos passos:</h3>";
echo "<ol>";
echo "<li>Se as tabelas não existem → Execute o SQL no phpMyAdmin</li>";
echo "<li>Se não há utilizadores → Este script criou um automaticamente</li>";
echo "<li>Tenta fazer login com: <strong>admin</strong> / <strong>password</strong></li>";
echo "<li>Depois apaga este ficheiro debug.php por segurança!</li>";
echo "</ol>";

echo "<br><a href='login.php' style='background:#e67e22; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>→ Ir para Login</a>";
?>
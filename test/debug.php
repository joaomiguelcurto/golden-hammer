<?php
// Configuração
require_once 'config/db.php';
$test_user = 'teste';
$test_email = 'teste@loujoa.pt';
$test_pass = 'teste';
$test_hash = '$2y$10$RBa82YvtRK4iF9B4d2UH4eaMxyi/sJTQqjgU4jYHLzkwndEkLcc/u'; // Hash de 'teste'

echo "Debug Base de Dados\n\n";

// Verificar tabelas
echo "1. Tabelas na base de dados:\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "ERRO: Nenhuma tabela encontrada.\n";
        echo "Ação necessária: Criar tabelas via SQL.\n\n";
    } else {
        echo "Sucesso. Tabelas encontradas: " . implode(', ', $tables) . "\n\n";
    }
} catch (PDOException $e) {
    echo "Erro ao listar tabelas: " . $e->getMessage() . "\n\n";
}

// Verificar e cria um utilizador de teste
echo "2. Utilizadores registados:\n";
try {
    // Mostrar utilizadores
    $stmt_select = $pdo->query("SELECT id, nome_utilizador, email FROM utilizadores");
    $users = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

    // Verificar se o utilizador de teste já existe
    $stmt_check = $pdo->prepare("SELECT id FROM utilizadores WHERE nome_utilizador = ? LIMIT 1");
    $stmt_check->execute([$test_user]);
    $admin_exists = $stmt_check->fetch();

    if (empty($users)) {
        echo "ERRO: Nenhum utilizador na base de dados.\n";

        // Tentar criar 'teste' se a tabela existir
        if (!$admin_exists) {
            $hash = password_hash($test_pass, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO utilizadores (nome_utilizador, email, password) VALUES (?, ?, ?)");
            $stmt_insert->execute([$test_user, $test_email, $hash]);

            echo "Utilizador '$test_user' criado. (Password: $test_pass)\n\n";
        }
    } else {
        echo "Sucesso. Total de utilizadores: " . count($users) . "\n";
        if ($admin_exists) {
            echo "Utilizador '$test_user' existe.\n\n";
        } else {
            echo "Utilizador '$test_user' não existe.\n\n";
        }
    }
} catch (PDOException $e) {
    echo "Erro ao aceder à tabela 'utilizadores': " . $e->getMessage() . "\n";
    echo "Provávelmente a tabela não existe.\n\n";
}

// Testa hash da password
echo "3. Testar verificação de password:\n";
if (password_verify($test_pass, $test_hash)) {
    echo "Sucesso. A função password_verify() está a funcionar.\n";
} else {
    echo "ERRO. Problema com password_verify().\n";
}

// Final
echo "\n--- FIM DO DEBUG ---\n";
echo "Instruções:\n";
echo "- Se houver ERRO na tabela, crie a tabela 'utilizadores'.\n";
echo "- Utilize $test_user / $test_pass para login.\n";
echo "- Apagar este ficheiro após o uso.\n";

?>
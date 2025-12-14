<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Verificar se está logado
verificarLogin();

$user_id = obterUserId();
$nome_utilizador = obterUserNome();

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar dados
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $preco_inicial = trim($_POST['preco_inicial'] ?? '');
    $duracao = trim($_POST['duracao'] ?? '');
    
    // Validações
    if (empty($nome)) {
        $erros[] = "O nome do item é obrigatório";
    } elseif (strlen($nome) > 100) {
        $erros[] = "O nome não pode ter mais de 100 caracteres";
    }
    
    if (empty($descricao)) {
        $erros[] = "A descrição é obrigatória";
    } elseif (strlen($descricao) < 20) {
        $erros[] = "A descrição deve ter pelo menos 20 caracteres";
    }
    
    if (empty($categoria)) {
        $erros[] = "Selecione uma categoria";
    }
    
    if (empty($preco_inicial) || !is_numeric($preco_inicial)) {
        $erros[] = "O preço inicial deve ser um valor numérico";
    } elseif ($preco_inicial < 1) {
        $erros[] = "O preço inicial deve ser pelo menos €1,00";
    }
    
    if (empty($duracao) || !in_array($duracao, ['1', '3', '7', '14'])) {
        $erros[] = "Selecione uma duração válida";
    }
    
    // Se não houver erros, criar item e leilão
    if (empty($erros)) {
        try {
            $pdo->beginTransaction();
            
            // Inserir item
            $stmt = $pdo->prepare("
                INSERT INTO itens (nome, descricao, categoria, dono_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nome, $descricao, $categoria, $user_id]);
            $item_id = $pdo->lastInsertId();
            
            // Calcular data de início e fim
            $inicio = date('Y-m-d H:i:s');
            $fim = date('Y-m-d H:i:s', strtotime("+{$duracao} days"));
            
            // Inserir leilão
            $stmt = $pdo->prepare("
                INSERT INTO leiloes (item_id, inicio, fim, preco_inicial, preco_atual, estado) 
                VALUES (?, ?, ?, ?, 0.00, 'ativo')
            ");
            $stmt->execute([$item_id, $inicio, $fim, $preco_inicial]);
            
            $pdo->commit();
            
            redirecionarComMensagem('inicio.php', 'Item criado e leilão iniciado com sucesso!', 'sucesso');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = "Erro ao criar item: " . $e->getMessage();
        }
    }
}

$categorias = obterCategorias();
$msg = obterMensagem();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Hammer - Criar Item</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/criar_item.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">Golden Hammer</div>
            <div class="user-info">
                <span class="user-name">Olá, <?= limpar($nome_utilizador) ?>!</span>
                <a href="inicio.php" class="btn btn-secondary">← Voltar</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </header>

        <?php if ($msg['mensagem']): ?>
            <div class="mensagem <?= $msg['tipo'] ?>">
                <?= limpar($msg['mensagem']) ?>
            </div>
        <?php endif; ?>

        <h1>🔨 Criar Novo Item para Leilão</h1>

        <div class="form-container">
            <?php if (!empty($erros)): ?>
                <div class="erro-lista">
                    <strong>⚠️ Erros encontrados:</strong>
                    <ul>
                        <?php foreach ($erros as $erro): ?>
                            <li><?= limpar($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card preview-info">
                <h3>📋 Informações Importantes</h3>
                <ul>
                    <li>O leilão será iniciado imediatamente após a criação</li>
                    <li>Não é possível editar ou cancelar após a criação</li>
                    <li>O sistema anti-sniping estende o leilão automaticamente</li>
                    <li>Descreva o item com o máximo de detalhes possível</li>
                </ul>
            </div>

            <div class="card">
                <form method="POST" action="" id="formCriarItem">
                    
                    <div class="form-group">
                        <label for="nome">Nome do Item *</label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            maxlength="100"
                            value="<?= isset($_POST['nome']) ? limpar($_POST['nome']) : '' ?>"
                            placeholder="Ex: iPhone 14 Pro Max 256GB"
                            required
                        >
                        <div class="char-counter" id="nomeCounter">0 / 100 caracteres</div>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição Detalhada *</label>
                        <textarea 
                            id="descricao" 
                            name="descricao" 
                            rows="6"
                            placeholder="Descreva o item em detalhe: estado, características, defeitos (se houver), motivo da venda, etc."
                            required
                        ><?= isset($_POST['descricao']) ? limpar($_POST['descricao']) : '' ?></textarea>
                        <small>Mínimo 20 caracteres. Seja detalhado para atrair mais interessados!</small>
                        <div class="char-counter" id="descricaoCounter">0 caracteres</div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="categoria">Categoria *</label>
                            <select id="categoria" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= limpar($cat) ?>" 
                                        <?= (isset($_POST['categoria']) && $_POST['categoria'] === $cat) ? 'selected' : '' ?>>
                                        <?= limpar($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="preco_inicial">Preço Inicial (€) *</label>
                            <input 
                                type="number" 
                                id="preco_inicial" 
                                name="preco_inicial" 
                                min="1" 
                                step="0.01"
                                value="<?= isset($_POST['preco_inicial']) ? limpar($_POST['preco_inicial']) : '' ?>"
                                placeholder="1.00"
                                required
                            >
                            <small>Valor mínimo: €1,00</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="duracao">Duração do Leilão *</label>
                        <select id="duracao" name="duracao" required>
                            <option value="">Selecione a duração</option>
                            <option value="1" <?= (isset($_POST['duracao']) && $_POST['duracao'] === '1') ? 'selected' : '' ?>>
                                1 dia
                            </option>
                            <option value="3" <?= (isset($_POST['duracao']) && $_POST['duracao'] === '3') ? 'selected' : '' ?>>
                                3 dias
                            </option>
                            <option value="7" <?= (isset($_POST['duracao']) && $_POST['duracao'] === '7') ? 'selected' : '' ?>>
                                7 dias (recomendado)
                            </option>
                            <option value="14" <?= (isset($_POST['duracao']) && $_POST['duracao'] === '14') ? 'selected' : '' ?>>
                                14 dias
                            </option>
                        </select>
                        <small>O leilão começará imediatamente após a criação</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large btn-block">
                            🔨 Criar Item e Iniciar Leilão
                        </button>
                    </div>
                    
                    <div class="form-actions">
                        <a href="inicio.php" class="btn btn-secondary btn-block">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        // Contador de caracteres para o nome
        const nomeInput = document.getElementById('nome');
        const nomeCounter = document.getElementById('nomeCounter');
        
        nomeInput.addEventListener('input', function() {
            const length = this.value.length;
            nomeCounter.textContent = `${length} / 100 caracteres`;
            if (length > 90) {
                nomeCounter.classList.add('warning');
            } else {
                nomeCounter.classList.remove('warning');
            }
        });

        // Contador de caracteres para a descrição
        const descricaoInput = document.getElementById('descricao');
        const descricaoCounter = document.getElementById('descricaoCounter');
        
        descricaoInput.addEventListener('input', function() {
            const length = this.value.length;
            descricaoCounter.textContent = `${length} caracteres`;
            if (length < 20) {
                descricaoCounter.classList.add('warning');
            } else {
                descricaoCounter.classList.remove('warning');
            }
        });

        // Inicializar contadores se houver valores
        if (nomeInput.value) {
            nomeInput.dispatchEvent(new Event('input'));
        }
        if (descricaoInput.value) {
            descricaoInput.dispatchEvent(new Event('input'));
        }

        // Confirmação antes de submeter
        document.getElementById('formCriarItem').addEventListener('submit', function(e) {
            const preco = document.getElementById('preco_inicial').value;
            const duracao = document.getElementById('duracao').options[document.getElementById('duracao').selectedIndex].text;
            
            if (!confirm(`Confirma a criação do leilão?\n\nPreço inicial: €${preco}\nDuração: ${duracao}\n\nO leilão será iniciado imediatamente e não poderá ser cancelado.`)) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>
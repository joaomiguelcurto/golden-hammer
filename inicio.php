<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Verificar se está logado
verificarLogin();

// Obter dados do utilizador
$user_id = obterUserId();
$nome_utilizador = obterUserNome();

// Finalizar leilões expirados automaticamente
finalizarLeiloesExpirados($pdo);

// Buscar leilões ativos em destaque (ordenados por proximidade do fim)
$stmt = $pdo->prepare("
    SELECT 
        l.id as leilao_id,
        l.fim,
        l.preco_inicial,
        l.preco_atual,
        l.estado,
        i.id as item_id,
        i.nome as item_nome,
        i.descricao as item_descricao,
        i.categoria,
        i.dono_id,
        u.nome_utilizador as dono_nome,
        (SELECT caminho FROM item_imagens WHERE item_id = i.id ORDER BY ordem ASC LIMIT 1) as primeira_imagem
    FROM leiloes l
    INNER JOIN itens i ON l.item_id = i.id
    INNER JOIN utilizadores u ON i.dono_id = u.id
    WHERE l.estado = 'ativo' AND l.fim > NOW()
    ORDER BY l.fim ASC
    LIMIT 12
");
$stmt->execute();
$leiloes = $stmt->fetchAll();

// Obter mensagem se houver
$msg = obterMensagem();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Hammer - Leilões em Destaque</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">Golden Hammer</div>
            <div class="user-info">
                <span class="user-name">Olá, <?= limpar($nome_utilizador) ?>!</span>
                <a href="criar_item.php" class="btn btn-primary">Criar Item</a>
                <a href="meus_leiloes.php" class="btn btn-secondary">Meus Leilões</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </header>

        <?php if ($msg['mensagem']): ?>
            <div class="mensagem <?= $msg['tipo'] ?>">
                <?= limpar($msg['mensagem']) ?>
            </div>
        <?php endif; ?>

        <h1>Leilões em Destaque</h1>

        <?php if (count($leiloes) > 0): ?>
            <div class="leiloes-grid">
                <?php foreach ($leiloes as $leilao): ?>
                    <?php
                    // Usar função para calcular tempo
                    $tempo_formatado = formatarTempoRestante($leilao['fim']);
                    
                    // Determinar preço a exibir
                    $preco_exibir = $leilao['preco_atual'] > 0 ? $leilao['preco_atual'] : $leilao['preco_inicial'];
                    
                    // Contar lances usando função
                    $num_lances = contarLances($pdo, $leilao['leilao_id']);
                    
                    // Verificar se o item pertence ao utilizador logado
                    $e_meu_item = ($leilao['dono_id'] == $user_id);
                    
                    // Verificar se o utilizador já deu lance neste leilão
                    $stmt_lance = $pdo->prepare("SELECT COUNT(*) FROM lances WHERE leilao_id = ? AND utilizador_id = ?");
                    $stmt_lance->execute([$leilao['leilao_id'], $user_id]);
                    $tem_lance = $stmt_lance->fetchColumn() > 0;
                    ?>
                    
                    <div class="leilao-card">
                        <?php if ($leilao['primeira_imagem']): ?>
                            <div class="leilao-imagem">
                                <img src="uploads/<?= limpar($leilao['primeira_imagem']) ?>" 
                                     alt="<?= limpar($leilao['item_nome']) ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="leilao-header">
                            <div class="item-nome"><?= limpar($leilao['item_nome']) ?></div>
                            <div class="leilao-badges">
                                <span class="categoria-badge"><?= limpar($leilao['categoria'] ?? 'Geral') ?></span>
                                <?php if ($e_meu_item): ?>
                                    <span class="categoria-badge badge-meu-item">Meu Item</span>
                                <?php endif; ?>
                                <?php if ($tem_lance && !$e_meu_item): ?>
                                    <span class="categoria-badge badge-com-lance">Com Lance</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="item-descricao"><?= limpar($leilao['item_descricao']) ?></div>
                        <div class="dono-info">Por: <?= limpar($leilao['dono_nome']) ?></div>
                        
                        <div class="preco-info">
                            <div>
                                <div class="preco-label">
                                    <?= $leilao['preco_atual'] > 0 ? 'Lance Atual' : 'Preço Inicial' ?>
                                </div>
                                <div class="preco-valor"><?= formatarMoeda($preco_exibir) ?></div>
                            </div>
                        </div>
                        
                        <div class="leilao-stats">
                            <span>Lances: <?= $num_lances ?> lance<?= $num_lances != 1 ? 's' : '' ?></span>
                            <span class="tempo-restante">Tempo: <?= $tempo_formatado ?></span>
                        </div>
                        
                        <a href="ver_leilao.php?id=<?= $leilao['leilao_id'] ?>">
                            <button class="btn-ver-leilao">Ver Leilão & Dar Lance</button>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sem-leiloes">
                <h2>Nenhum leilão ativo no momento</h2>
                <p>Seja o primeiro a criar um item para leiloar!</p>
                <a href="criar_item.php" class="btn btn-primary btn-large">Criar Primeiro Item</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Atualizar tempo restante a cada minuto
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
</body>

</html>
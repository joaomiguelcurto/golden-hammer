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

// Buscar leilões criados pelo utilizador (como dono)
$stmt_criados = $pdo->prepare("
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
        u.nome_utilizador as dono_nome,
        (SELECT caminho FROM item_imagens WHERE item_id = i.id ORDER BY ordem ASC LIMIT 1) as primeira_imagem,
        'criado' as tipo
    FROM leiloes l
    INNER JOIN itens i ON l.item_id = i.id
    INNER JOIN utilizadores u ON i.dono_id = u.id
    WHERE i.dono_id = ? AND l.estado IN ('ativo', 'terminado')
    ORDER BY l.fim DESC
");
$stmt_criados->execute([$user_id]);
$leiloes_criados = $stmt_criados->fetchAll();

// Buscar leilões onde o utilizador deu lance
$stmt_lances = $pdo->prepare("
    SELECT DISTINCT
        l.id as leilao_id,
        l.fim,
        l.preco_inicial,
        l.preco_atual,
        l.estado,
        i.id as item_id,
        i.nome as item_nome,
        i.descricao as item_descricao,
        i.categoria,
        u.nome_utilizador as dono_nome,
        (SELECT caminho FROM item_imagens WHERE item_id = i.id ORDER BY ordem ASC LIMIT 1) as primeira_imagem,
        'lance' as tipo
    FROM leiloes l
    INNER JOIN itens i ON l.item_id = i.id
    INNER JOIN utilizadores u ON i.dono_id = u.id
    INNER JOIN lances la ON l.id = la.leilao_id
    WHERE la.utilizador_id = ? AND l.estado IN ('ativo', 'terminado')
    ORDER BY l.fim DESC
");
$stmt_lances->execute([$user_id]);
$leiloes_lances = $stmt_lances->fetchAll();

// Combinar e remover duplicatas (um leilão pode ser ambos)
$leiloes_todos = array_merge($leiloes_criados, $leiloes_lances);
$seen = [];
$leiloes = [];
foreach ($leiloes_todos as $leilao) {
    if (!in_array($leilao['leilao_id'], $seen)) {
        $seen[] = $leilao['leilao_id'];
        $leiloes[] = $leilao;
    }
}

// Obter mensagem flash se houver
$msg = obterMensagem();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Hammer - Meus Leilões</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">Golden Hammer</div>
            <div class="user-info">
                <span class="user-name">Olá, <?= limpar($nome_utilizador) ?>!</span>
                <a href="criar_item.php" class="btn btn-primary">Criar Item</a>
                <a href="inicio.php" class="btn btn-secondary">Início</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </header>

        <?php if ($msg['mensagem']): ?>
            <div class="mensagem <?= $msg['tipo'] ?>">
                <?= limpar($msg['mensagem']) ?>
            </div>
        <?php endif; ?>

        <h1>Meus Leilões</h1>

        <?php if (count($leiloes) > 0): ?>
            <div class="leiloes-grid">
                <?php foreach ($leiloes as $leilao): ?>
                    <?php
                    $tempo_formatado = formatarTempoRestante($leilao['fim']);
                    $preco_exibir = $leilao['preco_atual'] > 0 ? $leilao['preco_atual'] : $leilao['preco_inicial'];
                    $num_lances = contarLances($pdo, $leilao['leilao_id']);

                    // Badges extras
                    $badge_extra = '';
                    if ($leilao['tipo'] === 'criado') {
                        $badge_extra = '<span class="categoria-badge badge-meu-item">Meu Item</span>';
                    } elseif ($leilao['tipo'] === 'lance') {
                        $badge_extra = '<span class="categoria-badge badge-com-lance">Com Lance</span>';
                    }

                    // Badge de vencedor
                    $vencedor_badge = '';
                    if ($leilao['estado'] === 'terminado') {
                        $stmt_v = $pdo->prepare("SELECT vencedor_id FROM leiloes WHERE id = ?");
                        $stmt_v->execute([$leilao['leilao_id']]);
                        $vencedor_id = $stmt_v->fetchColumn();
                        if ($vencedor_id == $user_id) {
                            $vencedor_badge = '<span class="categoria-badge badge-vencedor">Vencido!</span>';
                        }
                    }
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
                                <?= $badge_extra ?>
                                <?= $vencedor_badge ?>
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
                            <button class="btn-ver-leilao">Ver Leilão</button>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sem-leiloes">
                <h2>Ainda não participas em nenhum leilão</h2>
                <p>Cria o teu primeiro item ou explora os leilões em destaque para dar lance!</p>
                <a href="criar_item.php" class="btn btn-primary btn-large">Criar Item</a>
                <a href="inicio.php" class="btn btn-secondary btn-large" style="margin-left: 20px;">Ver Leilões em
                    Destaque</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Atualiza a página a cada minuto para refrescar tempos
        setInterval(() => location.reload(), 60000);
    </script>
</body>

</html>
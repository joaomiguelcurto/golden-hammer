<?php
require 'db.php';
include 'includes/header.php';

// Query otimizada para a tua base de dados real
$stmt = $pdo->query("
    SELECT 
        l.id, l.fim, l.preco_atual, l.estado,
        i.nome AS nome_item,
        i.categoria,
        COALESCE(COUNT(ln.id), 0) AS total_lances
    FROM leiloes l
    LEFT JOIN itens i ON l.item_id = i.id
    LEFT JOIN lances ln ON l.id = ln.leilao_id
    WHERE l.estado IN ('ativo', 'pendente')
    GROUP BY l.id
    ORDER BY l.fim ASC
    LIMIT 50
");

$leiloes = $stmt->fetchAll();
?>

<h2>Leilões em Curso</h2>

<?php if (empty($leiloes)): ?>
    <p style="text-align:center; padding:60px; font-size:1.3rem; color:#666;">
        Não há leilões ativos de momento.<br>
        Volta mais tarde ou cria o teu próprio leilão!
    </p>
<?php else: ?>
    <div class="auctions-grid">
        <?php foreach ($leiloes as $leilao): ?>
            <?php include 'partials/auction-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
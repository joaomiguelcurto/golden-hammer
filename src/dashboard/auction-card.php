<?php
$tempo_restante = strtotime($leilao['fim']) - time();
$terminado = $tempo_restante <= 0;
?>
<div class="auction-card <?= $terminado ? 'ended' : '' ?>">
    <div class="auction-image">
        <img src="assets/img/no-image.jpg" alt="<?= htmlspecialchars($leilao['nome_item']) ?>">
    </div>
    <div class="auction-info">
        <h3><a href="leilao.php?id=<?= $leilao['id'] ?>"><?= htmlspecialchars($leilao['nome_item']) ?></a></h3>
        <p class="category"><?= htmlspecialchars($leilao['categoria'] ?? 'Sem categoria') ?></p>
        
        <div class="price">
            <span class="label">Preço atual:</span>
            <strong><?= number_format($leilao['preco_atual'], 2, ',', '.') ?> €</strong>
        </div>

        <div class="timer" data-end="<?= $leilao['fim'] ?>">
            <?php if($terminado): ?>
                <span class="ended">Terminado</span>
            <?php else: ?>
                <span class="countdown">-</span>
            <?php endif; ?>
        </div>

        <div class="bids">
            Lances: <strong><?= $leilao['total_lances'] ?></strong>
        </div>

        <?php if(!$terminado && isset($_SESSION['user_id'])): ?>
            <a href="leilao.php?id=<?= $leilao['id'] ?>" class="btn">Dar lance</a>
        <?php elseif(!$terminado): ?>
            <a href="login.php" class="btn">Login para licitar</a>
        <?php else: ?>
            <span class="btn ended">Terminado</span>
        <?php endif; ?>
    </div>
</div>
<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Verificar se está logado
verificarLogin();

$user_id = obterUserId();
$nome_utilizador = obterUserNome();

// Obter ID do leilão
$leilao_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($leilao_id <= 0) {
    redirecionarComMensagem('inicio.php', 'Leilão não encontrado', 'erro');
}

// ======================================
// ENDPOINT AJAX PARA ATUALIZAÇÕES EM TEMPO REAL
// ======================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'atualizar') {
    header('Content-Type: application/json');
    
    try {
        // Buscar estado atual do leilão
        $stmt = $pdo->prepare("
            SELECT 
                l.preco_inicial,
                l.preco_atual,
                l.fim,
                l.estado
            FROM leiloes l
            WHERE l.id = ?
            LIMIT 1
        ");
        $stmt->execute([$leilao_id]);
        $leilao = $stmt->fetch();
        
        if (!$leilao) {
            echo json_encode(['erro' => 'Leilão não encontrado']);
            exit;
        }
        
        // Calcular tempo restante
        $tempo = calcularTempoRestante($leilao['fim']);
        
        // Obter preço atual
        $preco_atual = $leilao['preco_atual'] > 0 ? $leilao['preco_atual'] : $leilao['preco_inicial'];
        
        // Contar lances
        $num_lances = contarLances($pdo, $leilao_id);
        
        // Buscar histórico de lances
        $stmt = $pdo->prepare("
            SELECT 
                l.valor,
                l.data_hora,
                l.utilizador_id,
                u.nome_utilizador
            FROM lances l
            INNER JOIN utilizadores u ON l.utilizador_id = u.id
            WHERE l.leilao_id = ?
            ORDER BY l.data_hora DESC
            LIMIT 20
        ");
        $stmt->execute([$leilao_id]);
        $historico = $stmt->fetchAll();
        
        // Preparar dados do histórico
        $historico_formatado = array_map(function($lance) use ($user_id) {
            return [
                'valor' => $lance['valor'],
                'valor_formatado' => formatarMoeda($lance['valor']),
                'nome_utilizador' => $lance['nome_utilizador'],
                'data_hora' => $lance['data_hora'],
                'data_relativa' => formatarDataRelativa($lance['data_hora']),
                'eh_meu' => $lance['utilizador_id'] == $user_id
            ];
        }, $historico);
        
        // Retornar JSON
        echo json_encode([
            'sucesso' => true,
            'preco_atual' => $preco_atual,
            'preco_formatado' => formatarMoeda($preco_atual),
            'num_lances' => $num_lances,
            'tempo_restante' => $tempo,
            'tempo_formatado' => formatarTempoRestante($leilao['fim']),
            'expirado' => $tempo['expirado'],
            'proximo_lance_minimo' => $preco_atual + 1.00,
            'historico' => $historico_formatado,
            'timestamp' => time()
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['erro' => 'Erro ao buscar dados']);
        exit;
    }
}

// Processar lance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dar_lance'])) {
    $valor_lance = trim($_POST['valor_lance'] ?? '');
    
    if (empty($valor_lance) || !is_numeric($valor_lance)) {
        setMensagemErro('Por favor, insira um valor válido');
    } else {
        // Verificar se o leilão ainda está ativo
        if (!leilaoEstaAtivo($pdo, $leilao_id)) {
            setMensagemErro('Este leilão já não está ativo');
        } else {
            // Obter preço atual
            $stmt = $pdo->prepare("SELECT preco_inicial, preco_atual, item_id FROM leiloes WHERE id = ?");
            $stmt->execute([$leilao_id]);
            $leilao_info = $stmt->fetch();
            
            $preco_atual = $leilao_info['preco_atual'] > 0 ? $leilao_info['preco_atual'] : $leilao_info['preco_inicial'];
            
            // Verificar se o utilizador é o dono do item
            if (utilizadorEhDonoItem($pdo, $leilao_info['item_id'], $user_id)) {
                setMensagemErro('Não pode dar lances no seu próprio item!');
            } else {
                // Validar lance
                $validacao = validarLance($valor_lance, $preco_atual, 1.00);
                
                if (!$validacao['valido']) {
                    setMensagemErro($validacao['erro']);
                } else {
                    // Registrar lance
                    if (registrarLance($pdo, $leilao_id, $user_id, $valor_lance)) {
                        setMensagemSucesso('Lance registado com sucesso! 🎉');
                        header("Location: ver_leilao.php?id=$leilao_id");
                        exit;
                    } else {
                        setMensagemErro('Erro ao registar lance. Tente novamente.');
                    }
                }
            }
        }
    }
}

// Buscar informações do leilão
$stmt = $pdo->prepare("
    SELECT 
        l.id as leilao_id,
        l.inicio,
        l.fim,
        l.preco_inicial,
        l.preco_atual,
        l.estado,
        l.vencedor_id,
        i.id as item_id,
        i.nome as item_nome,
        i.descricao as item_descricao,
        i.categoria,
        i.criado_em as item_criado_em,
        u.id as dono_id,
        u.nome_utilizador as dono_nome
    FROM leiloes l
    INNER JOIN itens i ON l.item_id = i.id
    INNER JOIN utilizadores u ON i.dono_id = u.id
    WHERE l.id = ?
    LIMIT 1
");
$stmt->execute([$leilao_id]);
$leilao = $stmt->fetch();

if (!$leilao) {
    redirecionarComMensagem('inicio.php', 'Leilão não encontrado', 'erro');
}

// Buscar imagens do item
$stmt = $pdo->prepare("
    SELECT caminho, ordem 
    FROM item_imagens 
    WHERE item_id = ? 
    ORDER BY ordem ASC
");
$stmt->execute([$leilao['item_id']]);
$imagens = $stmt->fetchAll();

// Buscar histórico de lances
$stmt = $pdo->prepare("
    SELECT 
        l.valor,
        l.data_hora,
        u.nome_utilizador
    FROM lances l
    INNER JOIN utilizadores u ON l.utilizador_id = u.id
    WHERE l.leilao_id = ?
    ORDER BY l.data_hora DESC
    LIMIT 50
");
$stmt->execute([$leilao_id]);
$historico_lances = $stmt->fetchAll();

// Calcular informações
$tempo = calcularTempoRestante($leilao['fim']);
$preco_atual = $leilao['preco_atual'] > 0 ? $leilao['preco_atual'] : $leilao['preco_inicial'];
$proximo_lance_minimo = $preco_atual + 1.00;
$num_lances = contarLances($pdo, $leilao_id);
$eh_dono = utilizadorEhDonoItem($pdo, $leilao['item_id'], $user_id);
$ultimo_lance = obterUltimoLance($pdo, $leilao_id);

$msg = obterMensagem();
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Hammer - <?= limpar($leilao['item_nome']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/ver_leilao.css">
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

        <div class="leilao-container">
            <!-- Detalhes do Item -->
            <div class="detalhes-item">
                <!-- Galeria de Imagens -->
                <?php if (count($imagens) > 0): ?>
                <div class="galeria-container">
                    <div class="imagem-principal">
                        <img id="imagemPrincipal" src="uploads/<?= limpar($imagens[0]['caminho']) ?>" alt="<?= limpar($leilao['item_nome']) ?>">
                        <div class="galeria-navegacao">
                            <button class="btn-nav btn-prev" onclick="imagemAnterior()">❮</button>
                            <button class="btn-nav btn-next" onclick="proximaImagem()">❯</button>
                        </div>
                        <div class="contador-imagens">
                            <span id="imagemAtual">1</span> / <?= count($imagens) ?>
                        </div>
                    </div>
                    
                    <?php if (count($imagens) > 1): ?>
                    <div class="miniaturas">
                        <?php foreach ($imagens as $index => $img): ?>
                            <div class="miniatura <?= $index === 0 ? 'ativa' : '' ?>" 
                                 onclick="mudarImagem(<?= $index ?>)">
                                <img src="uploads/<?= limpar($img['caminho']) ?>" 
                                     alt="Miniatura <?= $index + 1 ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="item-header">
                    <h1><?= limpar($leilao['item_nome']) ?></h1>
                    <div class="item-meta">
                        <span>
                            <strong>Categoria:</strong> <?= limpar($leilao['categoria']) ?>
                        </span>
                        <span>
                            <strong>Vendedor:</strong> <?= limpar($leilao['dono_nome']) ?>
                        </span>
                        <span>
                            <strong>Publicado:</strong> <?= formatarDataRelativa($leilao['item_criado_em']) ?>
                        </span>
                    </div>
                </div>

                <div class="info-leilao">
                    <h3>Informações do Leilão</h3>
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span class="info-value">
                            <?php
                            if ($tempo['expirado']) {
                                echo 'Terminado';
                            } elseif ($leilao['estado'] === 'ativo') {
                                echo 'Ativo';
                            } else {
                                echo ucfirst($leilao['estado']);
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Início:</span>
                        <span class="info-value"><?= formatarData($leilao['inicio']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Término:</span>
                        <span class="info-value"><?= formatarData($leilao['fim']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Preço Inicial:</span>
                        <span class="info-value"><?= formatarMoeda($leilao['preco_inicial']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total de Lances:</span>
                        <span class="info-value"><?= $num_lances ?></span>
                    </div>
                </div>

                <h3 style="color: var(--cor-primaria); margin-bottom: 15px;">Descrição</h3>
                <div class="descricao-completa">
                    <?= limpar($leilao['item_descricao']) ?>
                </div>
            </div>

            <!-- Painel de Lances -->
            <div class="painel-lance">
                <div class="preco-destaque">
                    <div class="preco-destaque-label">
                        <?= $leilao['preco_atual'] > 0 ? 'Lance Atual' : 'Preço Inicial' ?>
                    </div>
                    <div class="preco-destaque-valor">
                        <?= formatarMoeda($preco_atual) ?>
                    </div>
                </div>

                <div class="tempo-box <?= $tempo['expirado'] ? 'expirado' : '' ?>">
                    <div class="tempo-label">
                        <?= $tempo['expirado'] ? 'Leilão Terminado' : 'Tempo Restante' ?>
                    </div>
                    <div class="tempo-valor" id="tempo-restante">
                        <?= $tempo['expirado'] ? 'Finalizado' : formatarTempoRestante($leilao['fim']) ?>
                    </div>
                </div>

                <?php if ($eh_dono): ?>
                    <div class="alerta-dono">
                        Este é o seu item. Não pode dar lances.
                    </div>
                <?php elseif ($tempo['expirado']): ?>
                    <div class="alerta-dono" style="background: var(--cor-perigo);">
                        Este leilão já terminou
                    </div>
                <?php else: ?>
                    <form method="POST" class="form-lance">
                        <div class="form-group">
                            <label for="valor_lance">Seu Lance</label>
                            <input 
                                type="number" 
                                id="valor_lance" 
                                name="valor_lance" 
                                min="<?= $proximo_lance_minimo ?>" 
                                step="0.01"
                                value="<?= $proximo_lance_minimo ?>"
                                required
                            >
                            <small>Lance mínimo: <?= formatarMoeda($proximo_lance_minimo) ?></small>
                        </div>

                        <button type="submit" name="dar_lance" class="btn btn-primary btn-large btn-block">
                            Dar Lance
                        </button>

                        <div class="lance-rapido">
                            <button type="button" class="btn-lance-rapido" onclick="setLance(<?= $proximo_lance_minimo ?>)">
                                Mínimo<br><?= formatarMoeda($proximo_lance_minimo) ?>
                            </button>
                            <button type="button" class="btn-lance-rapido" onclick="setLance(<?= $proximo_lance_minimo * 1.15 ?>)">
                                +15%<br><?= formatarMoeda($proximo_lance_minimo * 1.15) ?>
                            </button>
                            <button type="button" class="btn-lance-rapido" onclick="setLance(<?= $proximo_lance_minimo * 1.30 ?>)">
                                +30%<br><?= formatarMoeda($proximo_lance_minimo * 1.30) ?>
                            </button>
                            <button type="button" class="btn-lance-rapido" onclick="setLance(<?= $proximo_lance_minimo * 1.50 ?>)">
                                +50%<br><?= formatarMoeda($proximo_lance_minimo * 1.50) ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="historico-lances">
                    <h3>Histórico de Lances</h3>
                    <?php if (count($historico_lances) > 0): ?>
                        <?php foreach ($historico_lances as $index => $lance): ?>
                            <div class="lance-item <?= $lance['nome_utilizador'] === $nome_utilizador ? 'meu-lance' : '' ?>">
                                <div class="lance-valor">
                                    <?= formatarMoeda($lance['valor']) ?>
                                    <?php if ($index === 0 && !$tempo['expirado']): ?>
                                        <span class="badge-vencedor">VENCENDO</span>
                                    <?php endif; ?>
                                </div>
                                <div class="lance-info">
                                    <span><?= limpar($lance['nome_utilizador']) ?></span>
                                    <span><?= formatarDataRelativa($lance['data_hora']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="sem-lances">
                            Nenhum lance ainda. Seja o primeiro!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Galeria de imagens
        const imagens = <?= json_encode(array_column($imagens, 'caminho')) ?>;
        let imagemAtualIndex = 0;

        function mudarImagem(index) {
            imagemAtualIndex = index;
            document.getElementById('imagemPrincipal').src = 'uploads/' + imagens[index];
            document.getElementById('imagemAtual').textContent = index + 1;
            
            // Atualizar miniaturas
            document.querySelectorAll('.miniatura').forEach((mini, i) => {
                mini.classList.toggle('ativa', i === index);
            });
        }

        function proximaImagem() {
            imagemAtualIndex = (imagemAtualIndex + 1) % imagens.length;
            mudarImagem(imagemAtualIndex);
        }

        function imagemAnterior() {
            imagemAtualIndex = (imagemAtualIndex - 1 + imagens.length) % imagens.length;
            mudarImagem(imagemAtualIndex);
        }

        // Navegação por teclado
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') imagemAnterior();
            if (e.key === 'ArrowRight') proximaImagem();
        });

        // Função para definir lance rápido
        function setLance(valor) {
            document.getElementById('valor_lance').value = valor.toFixed(2);
        }

        // Atualizar tempo restante
        <?php if (!$tempo['expirado']): ?>
        let segundosRestantes = <?= $tempo['total_segundos'] ?>;
        
        function atualizarTempo() {
            if (segundosRestantes <= 0) {
                document.getElementById('tempo-restante').innerHTML = 'Finalizado';
                setTimeout(() => location.reload(), 2000);
                return;
            }
            
            const dias = Math.floor(segundosRestantes / (60 * 60 * 24));
            const horas = Math.floor((segundosRestantes % (60 * 60 * 24)) / (60 * 60));
            const minutos = Math.floor((segundosRestantes % (60 * 60)) / 60);
            const segundos = segundosRestantes % 60;
            
            let texto = '';
            if (dias > 0) {
                texto = `${dias}d ${horas}h ${minutos}m`;
            } else if (horas > 0) {
                texto = `${horas}h ${minutos}m ${segundos}s`;
            } else if (minutos > 0) {
                texto = `${minutos}m ${segundos}s`;
            } else {
                texto = `${segundos}s`;
            }
            
            document.getElementById('tempo-restante').textContent = texto;
            segundosRestantes--;
        }
        
        setInterval(atualizarTempo, 1000);
        <?php endif; ?>

        // ========================================
        // SISTEMA DE ATUALIZAÇÕES EM TEMPO REAL (AJAX)
        // ========================================
        <?php if (!$tempo['expirado']): ?>
        let ultimoPreco = <?= $preco_atual ?>;
        let ultimoNumLances = <?= $num_lances ?>;

        function atualizarLeilao() {
            fetch('ver_leilao.php?id=<?= $leilao_id ?>&ajax=atualizar')
                .then(response => response.json())
                .then(dados => {
                    if (dados.erro) {
                        console.error('Erro:', dados.erro);
                        return;
                    }

                    // Verificar se houve mudanças
                    const precoMudou = dados.preco_atual !== ultimoPreco;
                    const novoLance = dados.num_lances > ultimoNumLances;

                    // Atualizar preço atual
                    const precoElemento = document.querySelector('.preco-destaque-valor');
                    if (precoElemento && precoMudou) {
                        precoElemento.textContent = dados.preco_formatado;
                        
                        // Animação de destaque
                        precoElemento.style.animation = 'none';
                        setTimeout(() => {
                            precoElemento.style.animation = 'pulsar 0.5s ease-in-out';
                        }, 10);
                    }

                    // Atualizar label do preço
                    const labelElemento = document.querySelector('.preco-destaque-label');
                    if (labelElemento && dados.num_lances > 0) {
                        labelElemento.textContent = 'Lance Atual';
                    }

                    // Atualizar histórico se houve mudanças
                    if (novoLance) {
                        atualizarHistorico(dados.historico, dados.expirado);
                        
                        // Mostrar notificação
                        mostrarNotificacao('💰 Novo lance!', dados.preco_formatado);
                    }

                    // Atualizar campo de lance mínimo
                    const inputLance = document.getElementById('valor_lance');
                    if (inputLance && !dados.expirado) {
                        const valorMinimo = dados.proximo_lance_minimo.toFixed(2);
                        inputLance.min = valorMinimo;
                        
                        if (parseFloat(inputLance.value) < parseFloat(valorMinimo)) {
                            inputLance.value = valorMinimo;
                        }
                        
                        const smallText = inputLance.parentElement.querySelector('small');
                        if (smallText) {
                            smallText.textContent = `Lance mínimo: €${valorMinimo.replace('.', ',')}`;
                        }
                        
                        atualizarBotoesLanceRapido(dados.proximo_lance_minimo);
                    }

                    // Se leilão expirou
                    if (dados.expirado && !ultimoPreco.expirado) {
                        setTimeout(() => location.reload(), 2000);
                    }

                    // Atualizar valores para próxima comparação
                    ultimoPreco = dados.preco_atual;
                    ultimoNumLances = dados.num_lances;
                })
                .catch(error => {
                    console.error('Erro ao atualizar:', error);
                });
        }

        function atualizarHistorico(historico, expirado) {
            const container = document.querySelector('.historico-lances');
            if (!container) return;
            
            const titulo = container.querySelector('h3');
            const html = historico.length > 0 ? historico.map((lance, index) => `
                <div class="lance-item ${lance.eh_meu ? 'meu-lance' : ''}" style="animation: slideIn 0.3s ease-out">
                    <div class="lance-valor">
                        ${lance.valor_formatado}
                        ${index === 0 && !expirado ? '<span class="badge-vencedor">VENCENDO</span>' : ''}
                    </div>
                    <div class="lance-info">
                        <span>${lance.nome_utilizador}</span>
                        <span>${lance.data_relativa}</span>
                    </div>
                </div>
            `).join('') : '<div class="sem-lances">Nenhum lance ainda. Seja o primeiro!</div>';
            
            container.innerHTML = '';
            container.appendChild(titulo);
            container.insertAdjacentHTML('beforeend', html);
        }

        function atualizarBotoesLanceRapido(minimo) {
            const botoes = document.querySelectorAll('.btn-lance-rapido');
            if (botoes.length < 4) return;
            
            const valores = [minimo, minimo * 1.15, minimo * 1.30, minimo * 1.50];
            const labels = ['Mínimo', '+15%', '+30%', '+50%'];
            
            botoes.forEach((btn, index) => {
                const valor = valores[index].toFixed(2);
                btn.onclick = () => setLance(valores[index]);
                btn.innerHTML = `${labels[index]}<br>€${valor.replace('.', ',')}`;
            });
        }

        function mostrarNotificacao(titulo, mensagem) {
            const notif = document.createElement('div');
            notif.className = 'notificacao-lance';
            notif.innerHTML = `<strong>${titulo}</strong><br>${mensagem}`;
            notif.style.cssText = `
                position: fixed; top: 20px; right: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; padding: 15px 25px; border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 10000;
                animation: slideInRight 0.4s ease-out;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            `;
            
            document.body.appendChild(notif);
            
            setTimeout(() => {
                notif.style.animation = 'slideOutRight 0.4s ease-out';
                setTimeout(() => notif.remove(), 400);
            }, 4000);
        }

        // Adicionar animações CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
            @keyframes slideIn { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
            @keyframes pulsar { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        `;
        document.head.appendChild(style);

        // Atualizar a cada 3 segundos
        setInterval(atualizarLeilao, 3000);
        <?php endif; ?>
    </script>
</body>

</html>
<?php

/**
 * Verifica se o utilizador está logado
 * Redireciona para login se não estiver
 */
function verificarLogin() {
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Obtém o ID do utilizador atual
 * @return int|null
 */
function obterUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtém o nome do utilizador atual
 * @return string|null
 */
function obterUserNome() {
    return $_SESSION['nome_utilizador'] ?? null;
}

/**
 * Define uma mensagem de sucesso na sessão
 * @param string $mensagem
 */
function setMensagemSucesso($mensagem) {
    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['tipo_mensagem'] = 'sucesso';
}

/**
 * Define uma mensagem de erro na sessão
 * @param string $mensagem
 */
function setMensagemErro($mensagem) {
    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['tipo_mensagem'] = 'erro';
}

/**
 * Obtém e limpa a mensagem da sessão
 * @return array ['mensagem' => string, 'tipo' => string]
 */
function obterMensagem() {
    $mensagem = $_SESSION['mensagem'] ?? '';
    $tipo = $_SESSION['tipo_mensagem'] ?? 'sucesso';
    
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
    
    return ['mensagem' => $mensagem, 'tipo' => $tipo];
}

/**
 * Formata valor monetário em euros
 * @param float $valor
 * @return string
 */
function formatarMoeda($valor) {
    return '€' . number_format($valor, 2, ',', '.');
}

/**
 * Calcula tempo restante de um leilão
 * @param string $dataFim - Data no formato MySQL (Y-m-d H:i:s)
 * @return array ['dias' => int, 'horas' => int, 'minutos' => int, 'segundos' => int, 'total_segundos' => int, 'expirado' => bool]
 */
function calcularTempoRestante($dataFim) {
    $fim = strtotime($dataFim);
    $agora = time();
    $diff = $fim - $agora;
    
    if ($diff <= 0) {
        return [
            'dias' => 0,
            'horas' => 0,
            'minutos' => 0,
            'segundos' => 0,
            'total_segundos' => 0,
            'expirado' => true
        ];
    }
    
    return [
        'dias' => floor($diff / (60 * 60 * 24)),
        'horas' => floor(($diff % (60 * 60 * 24)) / (60 * 60)),
        'minutos' => floor(($diff % (60 * 60)) / 60),
        'segundos' => $diff % 60,
        'total_segundos' => $diff,
        'expirado' => false
    ];
}

/**
 * Formata tempo restante para exibição amigável
 * @param string $dataFim
 * @return string
 */
function formatarTempoRestante($dataFim) {
    $tempo = calcularTempoRestante($dataFim);
    
    if ($tempo['expirado']) {
        return 'Expirado';
    }
    
    if ($tempo['dias'] > 0) {
        return $tempo['dias'] . 'd ' . $tempo['horas'] . 'h';
    } elseif ($tempo['horas'] > 0) {
        return $tempo['horas'] . 'h ' . $tempo['minutos'] . 'm';
    } elseif ($tempo['minutos'] > 0) {
        return $tempo['minutos'] . 'm ' . $tempo['segundos'] . 's';
    } else {
        return $tempo['segundos'] . 's';
    }
}

/**
 * Sanitiza string para output HTML
 * @param string $str
 * @return string
 */
function limpar($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se um leilão está ativo
 * @param PDO $pdo
 * @param int $leilaoId
 * @return bool
 */
function leilaoEstaAtivo($pdo, $leilaoId) {
    $stmt = $pdo->prepare("SELECT estado, fim FROM leiloes WHERE id = ? LIMIT 1");
    $stmt->execute([$leilaoId]);
    $leilao = $stmt->fetch();
    
    if (!$leilao) {
        return false;
    }
    
    return $leilao['estado'] === 'ativo' && strtotime($leilao['fim']) > time();
}

/**
 * Verifica se o utilizador é dono de um item
 * @param PDO $pdo
 * @param int $itemId
 * @param int $userId
 * @return bool
 */
function utilizadorEhDonoItem($pdo, $itemId, $userId) {
    $stmt = $pdo->prepare("SELECT dono_id FROM itens WHERE id = ? LIMIT 1");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    return $item && $item['dono_id'] == $userId;
}

/**
 * Obtém o último lance de um leilão
 * @param PDO $pdo
 * @param int $leilaoId
 * @return array|null
 */
function obterUltimoLance($pdo, $leilaoId) {
    $stmt = $pdo->prepare("
        SELECT l.*, u.nome_utilizador 
        FROM lances l
        INNER JOIN utilizadores u ON l.utilizador_id = u.id
        WHERE l.leilao_id = ?
        ORDER BY l.valor DESC, l.data_hora DESC
        LIMIT 1
    ");
    $stmt->execute([$leilaoId]);
    return $stmt->fetch();
}

/**
 * Conta o número de lances de um leilão
 * @param PDO $pdo
 * @param int $leilaoId
 * @return int
 */
function contarLances($pdo, $leilaoId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lances WHERE leilao_id = ?");
    $stmt->execute([$leilaoId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Valida se um valor de lance é válido
 * @param float $novoLance
 * @param float $lanceAtual
 * @param float $incrementoMinimo (default: 1.00)
 * @return array ['valido' => bool, 'erro' => string]
 */
function validarLance($novoLance, $lanceAtual, $incrementoMinimo = 1.00) {
    if ($novoLance <= 0) {
        return ['valido' => false, 'erro' => 'O valor do lance deve ser positivo'];
    }
    
    if ($novoLance <= $lanceAtual) {
        return ['valido' => false, 'erro' => 'O lance deve ser superior ao lance atual'];
    }
    
    if (($novoLance - $lanceAtual) < $incrementoMinimo) {
        return ['valido' => false, 'erro' => 'O lance deve ser pelo menos €' . number_format($incrementoMinimo, 2, ',', '.') . ' superior'];
    }
    
    return ['valido' => true, 'erro' => ''];
}

/**
 * Implementa anti-sniping: estende o leilão se lance for nos últimos minutos
 * @param PDO $pdo
 * @param int $leilaoId
 * @param int $minutosFinais (default: 5)
 * @param int $extensao (default: 5 minutos)
 * @return bool true se foi estendido
 */
function antiSniping($pdo, $leilaoId, $minutosFinais = 5, $extensao = 5) {
    $stmt = $pdo->prepare("SELECT fim FROM leiloes WHERE id = ? LIMIT 1");
    $stmt->execute([$leilaoId]);
    $leilao = $stmt->fetch();
    
    if (!$leilao) {
        return false;
    }
    
    $fim = strtotime($leilao['fim']);
    $agora = time();
    $tempoRestante = $fim - $agora;
    
    // Se faltam menos de X minutos, estende o leilão
    if ($tempoRestante < ($minutosFinais * 60)) {
        $novoFim = date('Y-m-d H:i:s', $agora + ($extensao * 60));
        $stmt = $pdo->prepare("UPDATE leiloes SET fim = ? WHERE id = ?");
        $stmt->execute([$novoFim, $leilaoId]);
        return true;
    }
    
    return false;
}

/**
 * Registra um novo lance
 * @param PDO $pdo
 * @param int $leilaoId
 * @param int $userId
 * @param float $valor
 * @return bool
 */
function registrarLance($pdo, $leilaoId, $userId, $valor) {
    try {
        $pdo->beginTransaction();
        
        // Inserir lance
        $stmt = $pdo->prepare("
            INSERT INTO lances (leilao_id, utilizador_id, valor) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$leilaoId, $userId, $valor]);
        
        // Atualizar preço atual do leilão
        $stmt = $pdo->prepare("UPDATE leiloes SET preco_atual = ? WHERE id = ?");
        $stmt->execute([$valor, $leilaoId]);
        
        // Anti-sniping
        antiSniping($pdo, $leilaoId);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Formata data para exibição amigável
 * @param string $data - Data no formato MySQL
 * @return string
 */
function formatarData($data) {
    $timestamp = strtotime($data);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Formata data relativa (ex: "há 2 horas")
 * @param string $data
 * @return string
 */
function formatarDataRelativa($data) {
    $timestamp = strtotime($data);
    $agora = time();
    $diff = $agora - $timestamp;
    
    if ($diff < 60) {
        return 'agora mesmo';
    } elseif ($diff < 3600) {
        $min = floor($diff / 60);
        return 'há ' . $min . ' minuto' . ($min > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $horas = floor($diff / 3600);
        return 'há ' . $horas . ' hora' . ($horas > 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $dias = floor($diff / 86400);
        return 'há ' . $dias . ' dia' . ($dias > 1 ? 's' : '');
    } else {
        return formatarData($data);
    }
}

/**
 * Obtém estatísticas de um utilizador
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function obterEstatisticasUtilizador($pdo, $userId) {
    // Itens criados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM itens WHERE dono_id = ?");
    $stmt->execute([$userId]);
    $itens_criados = $stmt->fetchColumn();
    
    // Lances dados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lances WHERE utilizador_id = ?");
    $stmt->execute([$userId]);
    $lances_dados = $stmt->fetchColumn();
    
    // Leilões vencidos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leiloes WHERE vencedor_id = ?");
    $stmt->execute([$userId]);
    $leiloes_vencidos = $stmt->fetchColumn();
    
    return [
        'itens_criados' => $itens_criados,
        'lances_dados' => $lances_dados,
        'leiloes_vencidos' => $leiloes_vencidos
    ];
}

/**
 * Finaliza leilões expirados
 * @param PDO $pdo
 * @return int número de leilões finalizados
 */
function finalizarLeiloesExpirados($pdo) {
    try {
        // Buscar leilões ativos que já expiraram
        $stmt = $pdo->query("
            SELECT id FROM leiloes 
            WHERE estado = 'ativo' AND fim < NOW()
        ");
        $leiloes = $stmt->fetchAll();
        
        $finalizados = 0;
        
        foreach ($leiloes as $leilao) {
            // Buscar o último lance (vencedor)
            $ultimo = obterUltimoLance($pdo, $leilao['id']);
            
            if ($ultimo) {
                // Atualizar com vencedor
                $stmt = $pdo->prepare("
                    UPDATE leiloes 
                    SET estado = 'terminado', vencedor_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$ultimo['utilizador_id'], $leilao['id']]);
            } else {
                // Sem lances, apenas marca como terminado
                $stmt = $pdo->prepare("
                    UPDATE leiloes 
                    SET estado = 'terminado' 
                    WHERE id = ?
                ");
                $stmt->execute([$leilao['id']]);
            }
            
            $finalizados++;
        }
        
        return $finalizados;
        
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Gera URL com parâmetros
 * @param string $base
 * @param array $params
 * @return string
 */
function gerarUrl($base, $params = []) {
    if (empty($params)) {
        return $base;
    }
    
    return $base . '?' . http_build_query($params);
}

/**
 * Redireciona com mensagem
 * @param string $url
 * @param string $mensagem
 * @param string $tipo ('sucesso' ou 'erro')
 */
function redirecionarComMensagem($url, $mensagem, $tipo = 'sucesso') {
    if ($tipo === 'sucesso') {
        setMensagemSucesso($mensagem);
    } else {
        setMensagemErro($mensagem);
    }
    header("Location: $url");
    exit;
}

/**
 * Lista de categorias disponíveis
 * @return array
 */
function obterCategorias() {
    return [
        'Eletrónica',
        'Moda',
        'Casa & Jardim',
        'Desporto',
        'Arte',
        'Colecionáveis',
        'Automóveis',
        'Livros',
        'Joias',
        'Outros'
    ];
}

/**
 * Valida upload de imagem (para futuras implementações)
 * @param array $file - $_FILES['campo']
 * @return array ['valido' => bool, 'erro' => string, 'extensao' => string]
 */
function validarImagemUpload($file) {
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $tamanhoMaximo = 5 * 1024 * 1024; // 5MB
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valido' => false, 'erro' => 'Erro no upload do ficheiro', 'extensao' => ''];
    }
    
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $extensoesPermitidas)) {
        return ['valido' => false, 'erro' => 'Formato de imagem não permitido', 'extensao' => ''];
    }
    
    if ($file['size'] > $tamanhoMaximo) {
        return ['valido' => false, 'erro' => 'Imagem muito grande (máx: 5MB)', 'extensao' => ''];
    }
    
    return ['valido' => true, 'erro' => '', 'extensao' => $extensao];
}

?>
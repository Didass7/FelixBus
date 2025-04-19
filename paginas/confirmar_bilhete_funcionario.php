<?php
session_start();
include '../basedados/basedados.h';

/**
 * Sistema de confirmação de compra de bilhetes por funcionários
 * 
 * Esta página permite que funcionários confirmem a compra de bilhetes para clientes.
 * Diferente da página de compra normal, esta não verifica se o usuário é cliente,
 * mas sim se é funcionário ou administrador.
 */

// Verificar se o usuário está logado e é funcionário ou administrador
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

// Verificar se o ID do horário, a data e o ID do cliente foram fornecidos
if (!isset($_GET['id_horario']) || empty($_GET['id_horario']) || 
    !isset($_GET['data_viagem']) || empty($_GET['data_viagem']) ||
    !isset($_GET['id_cliente']) || empty($_GET['id_cliente'])) {
    $_SESSION['mensagem'] = "Informações necessárias não fornecidas.";
    header("Location: gerir_bilhetes.php");
    exit();
}

$id_horario = intval($_GET['id_horario']);
$data_viagem = $_GET['data_viagem'];
$id_cliente = intval($_GET['id_cliente']);
$id_funcionario = $_SESSION['id_utilizador'];
$mensagem = '';

// Validar a data
$data_atual = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+30 days'));

if ($data_viagem < $data_atual) {
    $_SESSION['mensagem'] = "Não é possível selecionar uma data passada.";
    header("Location: gerir_bilhetes.php?id_cliente=" . $id_cliente);
    exit();
}

if ($data_viagem > $data_limite) {
    $_SESSION['mensagem'] = "Só é possível comprar bilhetes para os próximos 30 dias.";
    header("Location: gerir_bilhetes.php?id_cliente=" . $id_cliente);
    exit();
}

// Buscar informações do cliente
$sql_cliente = "SELECT nome_completo, email FROM utilizadores WHERE id_utilizador = ? AND perfil = 'cliente'";
$stmt = mysqli_prepare($conn, $sql_cliente);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
$result_cliente = mysqli_stmt_get_result($stmt);
$cliente = mysqli_fetch_assoc($result_cliente);

if (!$cliente) {
    $_SESSION['mensagem'] = "Cliente não encontrado.";
    header("Location: gerir_bilhetes.php");
    exit();
}

// Buscar informações do horário com validação mais robusta
$sql_horario = "SELECT h.*, r.origem, r.destino,
                (SELECT COUNT(*) 
                 FROM bilhetes b 
                 WHERE b.id_horario = h.id_horario 
                 AND b.data_viagem = ?) as bilhetes_vendidos
                FROM horarios h 
                INNER JOIN rotas r ON h.id_rota = r.id_rota 
                WHERE h.id_horario = ?";

$stmt = mysqli_prepare($conn, $sql_horario);

if (!$stmt) {
    $_SESSION['mensagem'] = "Erro ao preparar a consulta: " . mysqli_error($conn);
    header("Location: gerir_bilhetes.php?id_cliente=" . $id_cliente);
    exit();
}

mysqli_stmt_bind_param($stmt, "si", $data_viagem, $id_horario);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['mensagem'] = "Erro ao executar a consulta: " . mysqli_stmt_error($stmt);
    header("Location: gerir_bilhetes.php?id_cliente=" . $id_cliente);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$horario = mysqli_fetch_assoc($result);

// Verificar se o horário existe e tem todos os dados necessários
if (!$horario || !isset($horario['preco']) || !isset($horario['lugares_disponiveis'])) {
    $_SESSION['mensagem'] = "Horário não encontrado ou dados incompletos.";
    header("Location: gerir_bilhetes.php?id_cliente=" . $id_cliente);
    exit();
}

// Buscar ou criar registro na viagens_diarias
$sql_viagem_diaria = "INSERT INTO viagens_diarias (id_horario, data_viagem, lugares_disponiveis)
                      SELECT ?, ?, h.lugares_disponiveis
                      FROM horarios h
                      WHERE h.id_horario = ?
                      ON DUPLICATE KEY UPDATE id_viagem_diaria = LAST_INSERT_ID(id_viagem_diaria)";

$stmt = mysqli_prepare($conn, $sql_viagem_diaria);
mysqli_stmt_bind_param($stmt, "isi", $id_horario, $data_viagem, $id_horario);
mysqli_stmt_execute($stmt);
$id_viagem_diaria = mysqli_insert_id($conn);

// Verificar lugares disponíveis para a data específica
$sql_verificar_lugares = "SELECT lugares_disponiveis 
                         FROM viagens_diarias 
                         WHERE id_viagem_diaria = ?";
$stmt = mysqli_prepare($conn, $sql_verificar_lugares);
mysqli_stmt_bind_param($stmt, "i", $id_viagem_diaria);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$viagem = mysqli_fetch_assoc($result);

if ($viagem['lugares_disponiveis'] <= 0) {
    $_SESSION['mensagem'] = "Não há lugares disponíveis para este horário na data selecionada.";
    header("Location: gerir_bilhetes.php?id_cliente=" . $id_cliente);
    exit();
}

// Função para gerar código de bilhete
function gerarCodigoBilhete($conn) {
    do {
        $codigo = '';
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        $sql = "SELECT 1 FROM bilhetes WHERE codigo_bilhete = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
    } while (mysqli_num_rows($resultado) > 0);
    
    return $codigo;
}

// Função para gerar lugar disponível
function gerarLugarDisponivel($conn, $id_horario, $data_viagem) {
    // Buscar lugares já ocupados para este horário e data
    $sql = "SELECT numero_lugar 
            FROM bilhetes 
            WHERE id_horario = ? 
            AND data_viagem = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $id_horario, $data_viagem);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $lugares_ocupados = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lugares_ocupados[] = $row['numero_lugar'];
    }
    
    // Buscar capacidade do autocarro
    $sql = "SELECT capacidade_autocarro FROM horarios WHERE id_horario = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_horario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $horario = mysqli_fetch_assoc($result);
    $capacidade = $horario['capacidade_autocarro'];
    
    // Gerar array com todos os lugares disponíveis
    $lugares_disponiveis = array_diff(range(1, $capacidade), $lugares_ocupados);
    
    // Se não houver lugares disponíveis, retorna false
    if (empty($lugares_disponiveis)) {
        return false;
    }
    
    // Seleciona um lugar aleatório dos disponíveis
    $lugar_aleatorio = array_rand(array_flip($lugares_disponiveis));
    
    return $lugar_aleatorio;
}

// Verificar saldo do cliente
$sql_carteira = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
$stmt = mysqli_prepare($conn, $sql_carteira);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
$result_carteira = mysqli_stmt_get_result($stmt);
$carteira_cliente = mysqli_fetch_assoc($result_carteira);

$saldo_suficiente = true;
if (!$carteira_cliente || $carteira_cliente['saldo'] < $horario['preco']) {
    $saldo_suficiente = false;
}

// Processar a compra apenas quando o formulário for submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        mysqli_begin_transaction($conn);
        
        // Verificar saldo do cliente novamente (pode ter mudado)
        $sql_carteira = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
        $stmt = mysqli_prepare($conn, $sql_carteira);
        mysqli_stmt_bind_param($stmt, "i", $id_cliente);
        mysqli_stmt_execute($stmt);
        $carteira_cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$carteira_cliente) {
            throw new Exception("Carteira do cliente não encontrada.");
        }

        if ($carteira_cliente['saldo'] < $horario['preco']) {
            throw new Exception("Saldo insuficiente. O cliente precisa carregar a carteira.");
        }

        // 1. Atualizar saldo do cliente
        $novo_saldo = $carteira_cliente['saldo'] - $horario['preco'];
        $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira_cliente['id_carteira']);
        mysqli_stmt_execute($stmt);

        // 2. Buscar carteira da empresa
        $sql_empresa = "SELECT id_carteira FROM carteiras WHERE tipo = 'empresa' LIMIT 1";
        $result_empresa = mysqli_query($conn, $sql_empresa);
        $carteira_empresa = mysqli_fetch_assoc($result_empresa);

        if (!$carteira_empresa) {
            throw new Exception("Carteira da empresa não encontrada.");
        }

        // 3. Atualizar saldo da empresa
        $sql_update = "UPDATE carteiras SET saldo = saldo + ? WHERE id_carteira = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "di", $horario['preco'], $carteira_empresa['id_carteira']);
        mysqli_stmt_execute($stmt);

        // 4. Registrar a transação
        $sql_trans = "INSERT INTO transacoes (id_carteira_origem, id_carteira_destino, valor, tipo, descricao) 
                     VALUES (?, ?, ?, 'bilhete', 'Compra de bilhete por funcionário')";
        $stmt = mysqli_prepare($conn, $sql_trans);
        mysqli_stmt_bind_param($stmt, "iid", 
            $carteira_cliente['id_carteira'], 
            $carteira_empresa['id_carteira'], 
            $horario['preco']
        );
        mysqli_stmt_execute($stmt);

        // 5. Criar o bilhete
        $codigo_bilhete = gerarCodigoBilhete($conn);
        $lugar = gerarLugarDisponivel($conn, $id_horario, $data_viagem);
        if (!$lugar) {
            throw new Exception("Não há lugares disponíveis.");
        }

        $sql_bilhete = "INSERT INTO bilhetes (codigo_bilhete, id_horario, id_utilizador, data_viagem, preco_pago, numero_lugar, comprado_por) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql_bilhete);
        mysqli_stmt_bind_param($stmt, "siisdii", 
            $codigo_bilhete,
            $id_horario, 
            $id_cliente, 
            $data_viagem,
            $horario['preco'],
            $lugar,
            $id_funcionario
        );
        mysqli_stmt_execute($stmt);

        // 6. Atualizar lugares disponíveis
        $sql_lugares = "UPDATE viagens_diarias 
                         SET lugares_disponiveis = lugares_disponiveis - 1 
                         WHERE id_viagem_diaria = ?";
        $stmt = mysqli_prepare($conn, $sql_lugares);
        mysqli_stmt_bind_param($stmt, "i", $id_viagem_diaria);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        $_SESSION['mensagem'] = "Bilhete comprado com sucesso para " . $cliente['nome_completo'] . "!";
        header("Location: gerir_bilhetes.php");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $mensagem = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Compra para Cliente - FelixBus</title>
    <link rel="stylesheet" href="comprar_bilhete.css">
    <style>
        .cliente-info {
            background: rgba(228, 188, 79, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gold-accent);
        }
        
        .cliente-info h3 {
            color: var(--gold-accent);
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }
        
        .cliente-info p {
            margin-bottom: 0.5rem;
        }
        
        .saldo-insuficiente {
            background-color: rgba(255, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .funcionario-badge {
            background-color: var(--gold-accent);
            color: var(--dark-bg);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
    <!-- Prevenir voltar usando o botão do navegador -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script>
        window.history.forward();
        function noBack() {
            window.history.forward();
        }
        
        // Prevenir reenvio do formulário ao atualizar a página
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>
<body onload="noBack();" onpageshow="if (event.persisted) noBack();" onunload="">
    <nav class="navbar">
        <div class="logo">
            <a href="<?php 
                if (isset($_SESSION['perfil'])) {
                    if ($_SESSION['perfil'] === 'cliente') {
                        echo 'pagina_inicial_cliente.php';
                    } elseif ($_SESSION['perfil'] === 'funcionário') {
                        echo 'pagina_inicial_funcionario.php';
                    } elseif ($_SESSION['perfil'] === 'administrador') {
                        echo 'pagina_inicial_admin.php';
                    }
                } else {
                    echo 'index.php';
                }
            ?>">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <?php if (isset($_SESSION['id_utilizador'])): ?>
                <?php if ($_SESSION['perfil'] === 'funcionário'): ?>
                    <a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php elseif ($_SESSION['perfil'] === 'administrador'): ?>
                    <a href="pagina_inicial_admin.php" class="nav-link">Painel de Administração</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="index.php" class="nav-link">Início</a>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Registar</a>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="container">
        <section class="compra-bilhete">
            <span class="funcionario-badge">Compra por Funcionário</span>
            <h2>Confirmar Compra de Bilhete para Cliente</h2>
            
            <?php if (!empty($mensagem)): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <div class="cliente-info">
                <h3>Informações do Cliente</h3>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome_completo']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                <?php if ($carteira_cliente): ?>
                <p><strong>Saldo Disponível:</strong> <?php echo number_format($carteira_cliente['saldo'], 2, ',', '.'); ?> €</p>
                <?php else: ?>
                <p><strong>Saldo Disponível:</strong> 0,00 €</p>
                <?php endif; ?>
            </div>

            <?php if (!$saldo_suficiente): ?>
                <div class="saldo-insuficiente">
                    <p>O cliente não possui saldo suficiente para esta compra.</p>
                    <p>Preço do bilhete: <?php echo number_format($horario['preco'], 2, ',', '.'); ?> €</p>
                    <p>O cliente precisa carregar a carteira antes de prosseguir.</p>
                </div>
            <?php endif; ?>

            <div class="detalhes-viagem">
                <h3>Detalhes da Viagem</h3>
                <p><strong>Origem:</strong> <?php echo htmlspecialchars($horario['origem']); ?></p>
                <p><strong>Destino:</strong> <?php echo htmlspecialchars($horario['destino']); ?></p>
                <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($data_viagem)); ?></p>
                <p><strong>Hora de Partida:</strong> <?php echo date('H:i', strtotime($horario['hora_partida'])); ?></p>
                <p><strong>Hora de Chegada:</strong> <?php echo date('H:i', strtotime($horario['hora_chegada'])); ?></p>
                <p><strong>Preço:</strong> <?php echo number_format($horario['preco'], 2, ',', '.'); ?> €</p>
                <p><strong>Lugares Disponíveis:</strong> <?php echo $viagem['lugares_disponiveis']; ?></p>
            </div>

            <form method="POST" action="">
                <?php if ($saldo_suficiente): ?>
                    <button type="submit" class="btn-primary">Confirmar Compra</button>
                <?php else: ?>
                    <button type="button" class="btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Saldo Insuficiente</button>
                <?php endif; ?>
                <a href="gerir_bilhetes.php?id_cliente=<?php echo $id_cliente; ?>" class="btn-secondary">Cancelar</a>
            </form>
        </section>
    </main>
</body>
</html>

<?php
/**
 * Confirmação de Compra de Bilhetes por Funcionários - FelixBus
 *
 * Esta página permite que funcionários comprem bilhetes para clientes.
 *
 * @author FelixBus
 * @version 1.0
 */

session_start();
include '../basedados/basedados.h';

// Verificar permissões de acesso
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

// Verificar parâmetros necessários
if (!isset($_GET['id_horario']) || !isset($_GET['data_viagem']) || !isset($_GET['id_cliente'])) {
    $_SESSION['mensagem'] = "Informações necessárias não fornecidas.";
    header("Location: gerir_bilhetes.php");
    exit();
}

// Inicializar variáveis
$id_horario = intval($_GET['id_horario']);
$data_viagem = $_GET['data_viagem'];
$id_cliente = intval($_GET['id_cliente']);
$id_funcionario = $_SESSION['id_utilizador'];
$mensagem = '';
$saldo_suficiente = true;

// Validar data da viagem
$data_atual = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+30 days'));

if ($data_viagem < $data_atual) {
    $_SESSION['mensagem'] = "Não é possível selecionar uma data passada.";
    header("Location: gerir_bilhetes.php?id_cliente={$id_cliente}");
    exit();
}

if ($data_viagem > $data_limite) {
    $_SESSION['mensagem'] = "Só é possível comprar bilhetes para os próximos 30 dias.";
    header("Location: gerir_bilhetes.php?id_cliente={$id_cliente}");
    exit();
}

// Obter informações do cliente
$stmt = $conn->prepare("SELECT nome_completo, email FROM utilizadores WHERE id_utilizador = ? AND perfil = 'cliente'");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();
$stmt->close();

if (!$cliente) {
    $_SESSION['mensagem'] = "Cliente não encontrado.";
    header("Location: gerir_bilhetes.php");
    exit();
}

// Obter informações do horário
$sql_horario = "SELECT h.*, r.origem, r.destino
                FROM horarios h
                INNER JOIN rotas r ON h.id_rota = r.id_rota
                WHERE h.id_horario = ?";
$stmt = $conn->prepare($sql_horario);
$stmt->bind_param("i", $id_horario);
$stmt->execute();
$result = $stmt->get_result();
$horario = $result->fetch_assoc();
$stmt->close();

if (!$horario || !isset($horario['preco'])) {
    $_SESSION['mensagem'] = "Horário não encontrado ou dados incompletos.";
    header("Location: gerir_bilhetes.php?id_cliente={$id_cliente}");
    exit();
}

// Obter ou criar registo na tabela viagens_diarias
$stmt = $conn->prepare("INSERT INTO viagens_diarias (id_horario, data_viagem, lugares_disponiveis)
                      SELECT ?, ?, h.lugares_disponiveis
                      FROM horarios h
                      WHERE h.id_horario = ?
                      ON DUPLICATE KEY UPDATE id_viagem_diaria = LAST_INSERT_ID(id_viagem_diaria)");
$stmt->bind_param("isi", $id_horario, $data_viagem, $id_horario);
$stmt->execute();
$id_viagem_diaria = $stmt->insert_id;
$stmt->close();

// Verificar lugares disponíveis
$stmt = $conn->prepare("SELECT lugares_disponiveis FROM viagens_diarias WHERE id_viagem_diaria = ?");
$stmt->bind_param("i", $id_viagem_diaria);
$stmt->execute();
$result = $stmt->get_result();
$viagem = $result->fetch_assoc();
$stmt->close();

if ($viagem['lugares_disponiveis'] <= 0) {
    $_SESSION['mensagem'] = "Não há lugares disponíveis para este horário na data selecionada.";
    header("Location: gerir_bilhetes.php?id_cliente={$id_cliente}");
    exit();
}

// Verificar saldo do cliente
$stmt = $conn->prepare("SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();
$carteira_cliente = $result->fetch_assoc();
$stmt->close();

if (!$carteira_cliente || $carteira_cliente['saldo'] < $horario['preco']) {
    $saldo_suficiente = false;
}

// Processar compra quando o formulário é submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $saldo_suficiente) {
    try {
        // Verificar saldo novamente (pode ter mudado)
        $stmt = $conn->prepare("SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $result = $stmt->get_result();
        $carteira_cliente = $result->fetch_assoc();
        $stmt->close();

        if (!$carteira_cliente) {
            throw new Exception("Carteira do cliente não encontrada.");
        }

        if ($carteira_cliente['saldo'] < $horario['preco']) {
            throw new Exception("Saldo insuficiente. O cliente precisa carregar a carteira.");
        }

        // 1. Atualizar saldo do cliente
        $novo_saldo = $carteira_cliente['saldo'] - (float)$horario['preco'];
        $stmt = $conn->prepare("UPDATE carteiras SET saldo = ? WHERE id_carteira = ?");
        $stmt->bind_param("di", $novo_saldo, $carteira_cliente['id_carteira']);
        $stmt->execute();
        $stmt->close();

        // 2. Obter carteira da empresa
        $stmt_empresa = $conn->prepare("SELECT id_carteira FROM carteiras WHERE tipo = 'empresa' LIMIT 1");
        $stmt_empresa->execute();
        $result_empresa = $stmt_empresa->get_result();
        $carteira_empresa = $result_empresa->fetch_assoc();
        $stmt_empresa->close();

        if (!$carteira_empresa) {
            throw new Exception("Carteira da empresa não encontrada.");
        }

        // 3. Atualizar saldo da empresa
        $stmt = $conn->prepare("UPDATE carteiras SET saldo = saldo + ? WHERE id_carteira = ?");
        $stmt->bind_param("di", $horario['preco'], $carteira_empresa['id_carteira']);
        $stmt->execute();
        $stmt->close();

        // 4. Registar transação
        $stmt = $conn->prepare("INSERT INTO transacoes (id_carteira_origem, id_carteira_destino, valor, tipo, descricao)
                              VALUES (?, ?, ?, 'bilhete', 'Compra de bilhete por funcionário')");
        $stmt->bind_param("iid",
            $carteira_cliente['id_carteira'],
            $carteira_empresa['id_carteira'],
            $horario['preco']
        );
        $stmt->execute();
        $stmt->close();

        // 5. Gerar código único para o bilhete
        do {
            $codigo_bilhete = '';
            $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for ($i = 0; $i < 8; $i++) {
                $codigo_bilhete .= $caracteres[rand(0, strlen($caracteres) - 1)];
            }

            $stmt = $conn->prepare("SELECT 1 FROM bilhetes WHERE codigo_bilhete = ?");
            $stmt->bind_param("s", $codigo_bilhete);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $exists = $resultado->num_rows > 0;
            $stmt->close();
        } while ($exists);

        // 6. Encontrar lugar disponível
        $stmt = $conn->prepare("SELECT numero_lugar FROM bilhetes WHERE id_horario = ? AND data_viagem = ?");
        $stmt->bind_param("is", $id_horario, $data_viagem);
        $stmt->execute();
        $result = $stmt->get_result();

        $lugares_ocupados = [];
        while ($row = $result->fetch_assoc()) {
            $lugares_ocupados[] = $row['numero_lugar'];
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT capacidade_autocarro FROM horarios WHERE id_horario = ?");
        $stmt->bind_param("i", $id_horario);
        $stmt->execute();
        $result = $stmt->get_result();
        $horario_capacidade = $result->fetch_assoc();
        $capacidade = $horario_capacidade['capacidade_autocarro'];
        $stmt->close();

        $lugares_disponiveis = array_diff(range(1, $capacidade), $lugares_ocupados);

        if (empty($lugares_disponiveis)) {
            throw new Exception("Não há lugares disponíveis.");
        }

        $lugar = array_rand(array_flip($lugares_disponiveis));

        // 7. Inserir bilhete
        $stmt = $conn->prepare("INSERT INTO bilhetes (codigo_bilhete, id_horario, id_utilizador, data_viagem, preco_pago, numero_lugar, comprado_por)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisdii",
            $codigo_bilhete,
            $id_horario,
            $id_cliente,
            $data_viagem,
            $horario['preco'],
            $lugar,
            $id_funcionario
        );
        $stmt->execute();
        $stmt->close();

        // 8. Atualizar lugares disponíveis
        $stmt = $conn->prepare("UPDATE viagens_diarias SET lugares_disponiveis = lugares_disponiveis - 1 WHERE id_viagem_diaria = ?");
        $stmt->bind_param("i", $id_viagem_diaria);
        $stmt->execute();
        $stmt->close();

        $_SESSION['mensagem'] = "Bilhete comprado com sucesso para {$cliente['nome_completo']}!";
        header("Location: gerir_bilhetes.php");
        exit();

    } catch (Exception $e) {
        $mensagem = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Compra para Cliente - FelixBus</title>
    <link rel="stylesheet" href="comprar_bilhete.css">
    <link rel="stylesheet" href="confirmar_bilhete_funcionario.css">
    <!-- Prevenir navegação para trás e reenvio de formulário -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script>
        // Prevenir voltar usando o botão do navegador
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
<body onload="noBack();" onpageshow="if (event.persisted) noBack();">
    <!-- Barra de navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <?php if ($_SESSION['perfil'] === 'funcionário'): ?>
                <a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>
            <?php else: ?>
                <a href="pagina_inicial_admin.php" class="nav-link">Painel</a>
            <?php endif; ?>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </nav>

    <!-- Conteúdo principal -->
    <main class="container">
        <section class="compra-bilhete">
            <!-- Distintivo e título -->
            <span class="funcionario-badge">Compra por Funcionário</span>
            <h2>Confirmar Compra de Bilhete para Cliente</h2>

            <!-- Mensagens de erro -->
            <?php if (!empty($mensagem)): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <!-- Informações do cliente -->
            <div class="cliente-info">
                <h3>Informações do Cliente</h3>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome_completo']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                <p><strong>Saldo Disponível:</strong>
                   <?php echo htmlspecialchars(number_format($carteira_cliente['saldo'] ?? 0, 2, ',', '.')); ?> €
                </p>
            </div>

            <!-- Alerta de saldo insuficiente -->
            <?php if (!$saldo_suficiente): ?>
                <div class="saldo-insuficiente">
                    <p>O cliente não possui saldo suficiente para esta compra.</p>
                    <p>Preço do bilhete: <?php echo htmlspecialchars(number_format($horario['preco'], 2, ',', '.')); ?> €</p>
                    <p>O cliente precisa carregar a carteira antes de prosseguir.</p>
                </div>
            <?php endif; ?>

            <!-- Detalhes da viagem -->
            <div class="detalhes-viagem">
                <h3>Detalhes da Viagem</h3>
                <p><strong>Origem:</strong> <?php echo htmlspecialchars($horario['origem']); ?></p>
                <p><strong>Destino:</strong> <?php echo htmlspecialchars($horario['destino']); ?></p>
                <p><strong>Data:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($data_viagem))); ?></p>
                <p><strong>Hora de Partida:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($horario['hora_partida']))); ?></p>
                <p><strong>Hora de Chegada:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($horario['hora_chegada']))); ?></p>
                <p><strong>Preço:</strong> <?php echo htmlspecialchars(number_format($horario['preco'], 2, ',', '.')); ?> €</p>
                <p><strong>Lugares Disponíveis:</strong> <?php echo htmlspecialchars($viagem['lugares_disponiveis']); ?></p>
            </div>

            <!-- Formulário de confirmação -->
            <form method="POST" action="">
                <?php if ($saldo_suficiente): ?>
                    <button type="submit" class="btn-primary">Confirmar Compra</button>
                <?php else: ?>
                    <button type="button" class="btn-disabled">Saldo Insuficiente</button>
                <?php endif; ?>
                <a href="gerir_bilhetes.php?id_cliente=<?= $id_cliente ?>" class="btn-secondary">Cancelar</a>
            </form>
        </section>
    </main>
</body>
</html>

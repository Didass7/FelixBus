<?php
session_start();
include '../basedados/basedados.h';

// Redirecionar para minhas viagens se a compra já foi concluída
if (isset($_SESSION['compra_concluida']) && $_SESSION['compra_concluida'] === true) {
    unset($_SESSION['compra_concluida']);
    header("Location: minhas_viagens.php");
    exit();
}

// Verificar se o utilizador está autenticado e tem perfil de cliente
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] != 'cliente') {
    header("Location: login.php");
    exit();
}

// Verificar se os parâmetros necessários foram fornecidos
if (!isset($_GET['id_horario']) || empty($_GET['id_horario']) ||
    !isset($_GET['data_viagem']) || empty($_GET['data_viagem'])) {
    $_SESSION['mensagem'] = "ID do horário ou data não fornecidos.";
    header("Location: consultar_rotas.php");
    exit();
}

$id_horario = intval($_GET['id_horario']);
$data_viagem = $_GET['data_viagem'];
$id_utilizador = $_SESSION['id_utilizador'];
$mensagem = '';

// Validar a data da viagem
$data_atual = date('Y-m-d');
$data_limite = date('Y-m-d', strtotime('+30 days'));

if ($data_viagem < $data_atual) {
    $_SESSION['mensagem'] = "Não é possível selecionar uma data passada.";
    header("Location: consultar_rotas.php");
    exit();
}

if ($data_viagem > $data_limite) {
    $_SESSION['mensagem'] = "Só é possível comprar bilhetes para os próximos 30 dias.";
    header("Location: consultar_rotas.php");
    exit();
}

// Obter informações do horário selecionado
$sql_horario = "SELECT h.*, r.origem, r.destino,
                (SELECT COUNT(*) FROM bilhetes b
                 WHERE b.id_horario = h.id_horario AND b.data_viagem = ?) as bilhetes_vendidos
                FROM horarios h
                INNER JOIN rotas r ON h.id_rota = r.id_rota
                WHERE h.id_horario = ?";

$stmt = $conn->prepare($sql_horario);

if (!$stmt) {
    $_SESSION['mensagem'] = "Erro ao preparar a consulta: " . mysqli_error($conn);
    header("Location: consultar_rotas.php");
    exit();
}

$stmt->bind_param("si", $data_viagem, $id_horario);

if (!$stmt->execute()) {
    $_SESSION['mensagem'] = "Erro ao executar a consulta: {$stmt->error}";
    header("Location: consultar_rotas.php");
    exit();
}

$result = $stmt->get_result();
$horario = $result->fetch_assoc();
$stmt->close();

// Verificar se o horário existe e contém todos os dados necessários
if (!$horario || !isset($horario['preco']) || !isset($horario['lugares_disponiveis'])) {
    $_SESSION['mensagem'] = "Horário não encontrado ou dados incompletos.";
    header("Location: consultar_rotas.php");
    exit();
}

// Criar ou obter registo na tabela viagens_diarias
$sql_viagem_diaria = "INSERT INTO viagens_diarias (id_horario, data_viagem, lugares_disponiveis)
                      SELECT ?, ?, h.lugares_disponiveis
                      FROM horarios h
                      WHERE h.id_horario = ?
                      ON DUPLICATE KEY UPDATE id_viagem_diaria = LAST_INSERT_ID(id_viagem_diaria)";

$stmt = $conn->prepare($sql_viagem_diaria);
$stmt->bind_param("isi", $id_horario, $data_viagem, $id_horario);
$stmt->execute();
$id_viagem_diaria = $stmt->insert_id;
$stmt->close();

// Verificar disponibilidade de lugares para a data específica
$sql_verificar_lugares = "SELECT lugares_disponiveis
                          FROM viagens_diarias
                          WHERE id_viagem_diaria = ?";
$stmt = $conn->prepare($sql_verificar_lugares);
$stmt->bind_param("i", $id_viagem_diaria);
$stmt->execute();
$result = $stmt->get_result();
$viagem = $result->fetch_assoc();
$stmt->close();

if ($viagem['lugares_disponiveis'] <= 0) {
    $_SESSION['mensagem'] = "Não há lugares disponíveis para este horário na data selecionada.";
    header("Location: consultar_rotas.php");
    exit();
}

// Processar a compra quando o formulário é submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar saldo da carteira do cliente
        $sql_carteira = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
        $stmt = $conn->prepare($sql_carteira);
        $stmt->bind_param("i", $id_utilizador);
        $stmt->execute();
        $result = $stmt->get_result();
        $carteira_cliente = $result->fetch_assoc();
        $stmt->close();

        if (!$carteira_cliente) {
            throw new Exception("Carteira do cliente não encontrada.");
        }

        if ($carteira_cliente['saldo'] < $horario['preco']) {
            throw new Exception("Saldo insuficiente. Por favor, carregue a sua carteira.");
        }

        // Atualizar saldo do cliente
        $novo_saldo = $carteira_cliente['saldo'] - (float)$horario['preco'];
        $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("di", $novo_saldo, $carteira_cliente['id_carteira']);
        $stmt->execute();
        $stmt->close();

        // Obter carteira da empresa
        $sql_empresa = "SELECT id_carteira FROM carteiras WHERE tipo = 'empresa' LIMIT 1";
        $stmt_empresa = $conn->prepare($sql_empresa);
        $stmt_empresa->execute();
        $result_empresa = $stmt_empresa->get_result();
        $carteira_empresa = $result_empresa->fetch_assoc();
        $stmt_empresa->close();

        if (!$carteira_empresa) {
            throw new Exception("Carteira da empresa não encontrada.");
        }

        // Atualizar saldo da empresa
        $sql_update = "UPDATE carteiras SET saldo = saldo + ? WHERE id_carteira = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("di", $horario['preco'], $carteira_empresa['id_carteira']);
        $stmt->execute();
        $stmt->close();

        // Registar a transação
        $sql_trans = "INSERT INTO transacoes (id_carteira_origem, id_carteira_destino, valor, tipo, descricao)
                      VALUES (?, ?, ?, 'bilhete', 'Compra de bilhete')";
        $stmt = $conn->prepare($sql_trans);
        $stmt->bind_param("iid",
            $carteira_cliente['id_carteira'],
            $carteira_empresa['id_carteira'],
            $horario['preco']
        );
        $stmt->execute();
        $stmt->close();

        // Gerar código único para o bilhete
        $codigo_bilhete = '';
        do {
            // Gerar código aleatório de 8 caracteres
            $codigo_bilhete = '';
            $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for ($i = 0; $i < 8; $i++) {
                $codigo_bilhete .= $caracteres[rand(0, strlen($caracteres) - 1)];
            }

            // Verificar se o código já existe
            $sql = "SELECT 1 FROM bilhetes WHERE codigo_bilhete = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $codigo_bilhete);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } while ($result->num_rows > 0);

        // Obter lugares já ocupados para este horário e data
        $sql = "SELECT numero_lugar
                FROM bilhetes
                WHERE id_horario = ? AND data_viagem = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id_horario, $data_viagem);
        $stmt->execute();
        $result = $stmt->get_result();

        $lugares_ocupados = [];
        while ($row = $result->fetch_assoc()) {
            $lugares_ocupados[] = $row['numero_lugar'];
        }
        $stmt->close();

        // Obter capacidade total do autocarro
        $sql = "SELECT capacidade_autocarro FROM horarios WHERE id_horario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_horario);
        $stmt->execute();
        $result = $stmt->get_result();
        $horario_capacidade = $result->fetch_assoc();
        $capacidade = $horario_capacidade['capacidade_autocarro'];
        $stmt->close();

        // Calcular lugares disponíveis
        $lugares_disponiveis = array_diff(range(1, $capacidade), $lugares_ocupados);

        // Verificar se há lugares disponíveis
        if (empty($lugares_disponiveis)) {
            throw new Exception("Não há lugares disponíveis.");
        }

        // Selecionar um lugar aleatório dos disponíveis
        $lugar = array_rand(array_flip($lugares_disponiveis));

        // Criar o bilhete
        $sql_bilhete = "INSERT INTO bilhetes (codigo_bilhete, id_horario, id_utilizador, data_viagem, preco_pago, numero_lugar)
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_bilhete);
        $stmt->bind_param("siisdi",
            $codigo_bilhete,
            $id_horario,
            $id_utilizador,
            $data_viagem,
            $horario['preco'],
            $lugar
        );
        $stmt->execute();
        $stmt->close();

        // Atualizar lugares disponíveis
        $sql_lugares = "UPDATE viagens_diarias
                        SET lugares_disponiveis = lugares_disponiveis - 1
                        WHERE id_viagem_diaria = ?";
        $stmt = $conn->prepare($sql_lugares);
        $stmt->bind_param("i", $id_viagem_diaria);
        $stmt->execute();
        $stmt->close();

        // Redirecionar
        $_SESSION['compra_concluida'] = true;
        header("Location: minhas_viagens.php");
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
    <title>Confirmar Compra - FelixBus</title>
    <link rel="stylesheet" href="comprar_bilhete.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script>
        // Prevenir navegação para trás
        window.history.forward();
        function noBack() {
            window.history.forward();
        }

        // Prevenir reenvio do formulário
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>
<body onload="noBack();" onpageshow="if (event.persisted) noBack();" onunload="">
    <nav class="navbar">
        <div class="logo">
            <a href="<?php
                if (isset($_SESSION['id_utilizador'])) {
                    switch($_SESSION['perfil']) {
                        case 'cliente':
                            echo 'pagina_inicial_cliente.php';
                            break;
                        case 'funcionário':
                            echo 'pagina_inicial_funcionario.php';
                            break;
                        case 'administrador':
                            echo 'pagina_inicial_admin.php';
                            break;
                        default:
                            echo 'index.php';
                    }
                } else {
                    echo 'index.php';
                }
            ?>">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <?php if (isset($_SESSION['id_utilizador']) && $_SESSION['perfil'] === 'cliente'): ?>
                <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
                <a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>
                <a href="carteira.php" class="nav-link">Carteira</a>
                <a href="perfil.php" class="nav-link">Perfil</a>
                <a href="logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="index.php" class="nav-link">Início</a>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Registar</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <section class="compra-bilhete">
            <h2>Confirmar Compra de Bilhete</h2>

            <?php if (!empty($mensagem)): ?>
                <div class="mensagem erro"><?php echo $mensagem; ?></div>
            <?php endif; ?>

            <div class="detalhes-viagem">
                <h3>Detalhes da Viagem</h3>
                <p><strong>Origem:</strong> <?php echo $horario['origem']; ?></p>
                <p><strong>Destino:</strong> <?php echo $horario['destino']; ?></p>
                <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($data_viagem)); ?></p>
                <p><strong>Hora de Partida:</strong> <?php echo date('H:i', strtotime($horario['hora_partida'])); ?></p>
                <p><strong>Hora de Chegada:</strong> <?php echo date('H:i', strtotime($horario['hora_chegada'])); ?></p>
                <p><strong>Preço:</strong> <?php echo number_format($horario['preco'], 2, ',', '.'); ?> €</p>
            </div>

            <form method="POST" action="">
                <button type="submit" class="btn-primary">Confirmar Compra</button>
                <a href="consultar_rotas.php" class="btn-secondary">Cancelar</a>
            </form>
        </section>
    </main>
</body>
</html>

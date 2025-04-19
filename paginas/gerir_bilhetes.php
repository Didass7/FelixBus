<?php
session_start();
include '../basedados/basedados.h';

// Verificar permissões
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

/**
 * Sistema de gestão de bilhetes
 *
 * Este sistema permite que funcionários comprem bilhetes para clientes.
 *
 * Funcionamento dos lugares disponíveis:
 * 1. Cada horário tem um número base de lugares disponíveis definido na tabela 'horarios'
 * 2. Para cada data específica, é criado um registro na tabela 'viagens_diarias'
 * 3. Quando um bilhete é comprado, o número de lugares disponíveis é reduzido apenas para aquela data
 * 4. Cada data começa com o número base de lugares disponíveis (ex: 50)
 * 5. Se em um dia X restam 49 lugares, no dia Y ainda haverá os 50 lugares originais
 */

// Inicializar mensagens
$mensagem = '';
$erro = '';

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

// Buscar todos os clientes
$sql_clientes = "SELECT u.id_utilizador, u.nome_completo, u.email, c.saldo
                 FROM utilizadores u
                 LEFT JOIN carteiras c ON u.id_utilizador = c.id_utilizador
                 WHERE u.perfil = 'cliente'
                 ORDER BY u.nome_completo ASC";
$result_clientes = mysqli_query($conn, $sql_clientes);

// Se um cliente foi selecionado, buscar rotas disponíveis
if (isset($_GET['id_cliente'])) {
    $id_cliente = $_GET['id_cliente'];

    // Buscar informações do cliente
    $sql_cliente = "SELECT nome_completo, email FROM utilizadores WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql_cliente);
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $cliente_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Buscar todas as origens e destinos distintos para os dropdowns
    $sql_origens = "SELECT DISTINCT origem FROM rotas WHERE origem IS NOT NULL ORDER BY origem";
    $result_origens = mysqli_query($conn, $sql_origens);
    $origens = [];
    while ($row = mysqli_fetch_assoc($result_origens)) {
        $origens[] = $row['origem'];
    }

    $sql_destinos = "SELECT DISTINCT destino FROM rotas WHERE destino IS NOT NULL ORDER BY destino";
    $result_destinos = mysqli_query($conn, $sql_destinos);
    $destinos = [];
    while ($row = mysqli_fetch_assoc($result_destinos)) {
        $destinos[] = $row['destino'];
    }

    // Inicializar variáveis usando o operador de coalescência nula (??)
    $origem = trim($_GET['origem'] ?? '');
    $destino = trim($_GET['destino'] ?? '');
    $data_viagem = $_GET['data_viagem'] ?? date('Y-m-d');
    $resultados = [];

    // IMPORTANTE: Este sistema garante que todas as rotas apareçam em todos os dias
    // e que os lugares disponíveis estejam corretamente ligados à tabela de viagens diárias.
    // Quando uma data é selecionada, o sistema verifica se existem registros para todos os horários
    // nessa data e cria os registros faltantes automaticamente.

    // Verificar e garantir que existam viagens diárias para todos os horários na data selecionada
    // Isto é crucial para que todas as rotas apareçam em todos os dias

    // Primeiro, buscar todos os horários disponíveis no sistema
    $sql_horarios = "SELECT id_horario, lugares_disponiveis FROM horarios";
    $result_horarios = mysqli_query($conn, $sql_horarios);
    $todos_horarios = [];

    // Armazenar todos os horários em um array para uso posterior
    while ($horario = mysqli_fetch_assoc($result_horarios)) {
        $todos_horarios[$horario['id_horario']] = $horario['lugares_disponiveis'];
    }

    // Verificar quais horários já têm registros para a data selecionada
    $sql_check_viagens = "SELECT id_horario FROM viagens_diarias WHERE data_viagem = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check_viagens);
    mysqli_stmt_bind_param($stmt_check, "s", $data_viagem);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    $horarios_existentes = [];
    while ($row = mysqli_fetch_assoc($result_check)) {
        $horarios_existentes[] = $row['id_horario'];
    }

    // Para cada horário que não tem registro na data selecionada, criar um novo registro
    // Isto garante que TODAS as rotas apareçam em TODOS os dias, mesmo que não tenham sido criadas anteriormente
    foreach ($todos_horarios as $id_horario => $lugares_disponiveis) {
        if (!in_array($id_horario, $horarios_existentes)) {
            // Inserir um novo registro na tabela viagens_diarias com os lugares disponíveis iniciais
            // Os lugares disponíveis são copiados da tabela de horários para manter a consistência
            // Isto garante que cada dia começa com o número base de lugares disponíveis
            $sql_insert = "INSERT INTO viagens_diarias (id_horario, data_viagem, lugares_disponiveis)
                           VALUES (?, ?, ?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "isi", $id_horario, $data_viagem, $lugares_disponiveis);
            mysqli_stmt_execute($stmt_insert);

            // Registrar no log que um novo registro foi criado (opcional, para debug)
            // error_log("Criado registro para horário ID: $id_horario na data: $data_viagem com $lugares_disponiveis lugares");
        }
    }

    // Buscar resultados se houver pesquisa
    // Usamos JOIN com a tabela viagens_diarias para obter os lugares disponíveis corretos para a data selecionada
    // Como já garantimos que todos os horários têm registros na tabela viagens_diarias, não precisamos de LEFT JOIN
    if (isset($_GET['pesquisar'])) {
        $sql_rotas = "SELECT h.id_horario, r.origem, r.destino,
                           TIME(h.hora_partida) as hora_partida,
                           TIME(h.hora_chegada) as hora_chegada,
                           h.preco,
                           vd.lugares_disponiveis as lugares_disponiveis_data
                    FROM horarios h
                    JOIN rotas r ON h.id_rota = r.id_rota
                    JOIN viagens_diarias vd ON h.id_horario = vd.id_horario AND vd.data_viagem = ?
                    WHERE 1=1";

        // Inicializar arrays e variáveis
        $params = [$data_viagem];
        $types = "s";

        if (!empty($origem) || !empty($destino)) {
            if (!empty($origem)) {
                $sql_rotas .= " AND r.origem = ?";
                $params[] = $origem;
                $types .= "s";
            }

            if (!empty($destino)) {
                $sql_rotas .= " AND r.destino = ?";
                $params[] = $destino;
                $types .= "s";
            }
        }

        $sql_rotas .= " ORDER BY h.hora_partida ASC";

        $stmt = mysqli_prepare($conn, $sql_rotas);

        if (!$stmt) {
            die("Erro na preparação da consulta: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (!mysqli_stmt_execute($stmt)) {
            die("Erro na execução da consulta: " . mysqli_error($conn));
        }

        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            die("Erro ao obter resultados: " . mysqli_error($conn));
        }

        $resultados = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Incluir as funções necessárias
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


?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Gestão de Bilhetes</title>
    <link rel="stylesheet" href="consultar_rotas.css">
    <style>
        /* Estilos específicos para gerir_bilhetes.php */
        main.container {
            padding: 120px 5% 60px;
            background-color: var(--dark-bg);
            min-height: 80vh;
        }

        .clients-list h2 {
            color: var(--gold-accent);
            margin-bottom: 20px;
            text-align: center;
            font-size: 2rem;
        }

        .rotas-section h2 {
            color: var(--gold-accent);
            margin-bottom: 20px;
            text-align: center;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                <?php if ($_SESSION['perfil'] === 'cliente'): ?>
                    <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
                    <a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>
                    <a href="carteira.php" class="nav-link">Carteira</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php elseif ($_SESSION['perfil'] === 'funcionário'): ?>
                    <a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php elseif ($_SESSION['perfil'] === 'administrador'): ?>
                    <a href="pagina_inicial_admin.php" class="nav-link">Painel de Administração</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="empresa.php" class="nav-link">Sobre Nós</a>
                <a href="register.php" class="nav-link">Registar</a>
                <a href="login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container" style="max-width: 100%; padding: 120px 2% 60px;">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if (!isset($_GET['id_cliente'])): ?>
            <!-- Lista de Clientes -->
            <section class="clients-list">
                <h2>Selecione um Cliente</h2>
                <div class="clients-grid">
                    <?php while ($cliente = mysqli_fetch_assoc($result_clientes)): ?>
                        <a href="?id_cliente=<?php echo $cliente['id_utilizador']; ?>" class="client-card">
                            <h3><?php echo htmlspecialchars($cliente['nome_completo']); ?></h3>
                            <p>Email: <?php echo htmlspecialchars($cliente['email']); ?></p>
                            <p>Saldo: <?php echo number_format($cliente['saldo'], 2); ?>€</p>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php else: ?>
            <!-- Consulta de Rotas e Compra de Bilhetes -->
            <section class="rotas-section">
                <h2>Comprar Bilhete para <?php echo htmlspecialchars($cliente_info['nome_completo']); ?></h2>
                <!-- Formulário de pesquisa -->
                <div class="hero-content">
                    <form class="search-form" method="GET" action="">
                        <input type="hidden" name="id_cliente" value="<?php echo $id_cliente; ?>">
                        <div class="form-group">
                            <label for="origem">Origem</label>
                            <select class="form-input" name="origem" id="origem">
                                <option value="">Todas as origens</option>
                                <?php foreach($origens as $cidade): ?>
                                    <option value="<?php echo htmlspecialchars($cidade); ?>"
                                            <?php echo ($cidade === $origem) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cidade); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="destino">Destino</label>
                            <select class="form-input" name="destino" id="destino">
                                <option value="">Todos os destinos</option>
                                <?php foreach($destinos as $cidade): ?>
                                    <option value="<?php echo htmlspecialchars($cidade); ?>"
                                            <?php echo ($cidade === $destino) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cidade); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="data_viagem">Data da Viagem</label>
                            <input type="date" class="form-input" name="data_viagem" id="data_viagem"
                                   value="<?php echo htmlspecialchars($data_viagem); ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                                   required>
                        </div>
                        <button class="btn-primary" name="pesquisar" type="submit">Pesquisar</button>
                    </form>
                </div>

                <!-- Results Section -->
                <div class="results-section">
                    <div class="container">
                        <?php if (!empty($resultados)): ?>
                            <h2 class="section-title">Resultados da Pesquisa</h2>
                            <div class="results-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Origem</th>
                                            <th>Destino</th>
                                            <th>Hora Partida</th>
                                            <th>Hora Chegada</th>
                                            <th>Preço</th>
                                            <th>Lugares Disponíveis</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($resultados as $rota): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rota['origem']); ?></td>
                                                <td><?php echo htmlspecialchars($rota['destino']); ?></td>
                                                <td><?php echo date('H:i', strtotime($rota['hora_partida'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($rota['hora_chegada'])); ?></td>
                                                <td><?php echo number_format($rota['preco'], 2, ',', '.') . ' €'; ?></td>
                                                <td><?php echo $rota['lugares_disponiveis_data']; ?></td>
                                                <td>
                                                    <?php if($rota['lugares_disponiveis_data'] > 0): ?>
                                                        <a href="confirmar_bilhete_funcionario.php?id_horario=<?php echo $rota['id_horario']; ?>&data_viagem=<?php echo urlencode($data_viagem); ?>&id_cliente=<?php echo $id_cliente; ?>"
                                                           class="btn-action">Comprar</a>
                                                    <?php else: ?>
                                                        <span class="esgotado">Esgotado</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <?php if (isset($_GET['pesquisar'])): ?>
                                <div class="no-results">
                                    <h2>Nenhum resultado encontrado</h2>
                                    <p>Tente outra combinação de origem e destino.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="back-button">
                    <a href="gerir_bilhetes.php" class="btn-secondary">Voltar para Lista de Clientes</a>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <style>
    /* Estilos adicionais para gerir_bilhetes.php */
    .clients-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .client-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 20px;
        text-decoration: none;
        color: var(--text-light);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .client-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        background: rgba(228, 188, 79, 0.1);
        border-color: var(--gold-accent);
    }

    .client-card h3 {
        margin: 0 0 10px 0;
        color: var(--gold-accent);
    }

    .client-card p {
        margin: 5px 0;
        color: var(--text-light);
    }

    /* Estilos para a seção de rotas */
    .rotas-section {
        padding: 20px 0;
    }

    /* Estilos para a tabela de resultados */
    .results-section .container {
        max-width: 100%;
        padding: 0;
    }

    .results-container {
        width: 100%;
        overflow-x: auto;
    }

    .results-table {
        min-width: 100%;
        table-layout: fixed;
    }

    .results-table th,
    .results-table td {
        padding: 12px 15px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Definir larguras específicas para as colunas */
    .results-table th:nth-child(1),
    .results-table td:nth-child(1) {
        width: 15%;
    }

    .results-table th:nth-child(2),
    .results-table td:nth-child(2) {
        width: 15%;
    }

    .results-table th:nth-child(3),
    .results-table td:nth-child(3),
    .results-table th:nth-child(4),
    .results-table td:nth-child(4) {
        width: 10%;
    }

    .results-table th:nth-child(5),
    .results-table td:nth-child(5),
    .results-table th:nth-child(6),
    .results-table td:nth-child(6) {
        width: 10%;
        text-align: center;
    }

    .results-table th:nth-child(7),
    .results-table td:nth-child(7) {
        width: 10%;
        text-align: center;
    }

    /* Estilos para o botão de voltar */
    .back-button {
        margin-top: 30px;
        text-align: center;
    }

    .btn-secondary {
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--text-light);
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        transition: all var(--transition-speed);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-secondary:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    /* Estilos para o botão de ação */
    .btn-action {
        background-color: var(--gold-accent);
        color: var(--dark-bg);
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        display: inline-block;
        font-weight: bold;
        transition: all var(--transition-speed);
        white-space: nowrap;
        text-align: center;
    }

    .btn-action:hover {
        background-color: var(--gold-accent-hover);
        transform: translateY(-2px);
    }

    .esgotado {
        color: #e74c3c;
        font-weight: bold;
        display: inline-block;
        padding: 8px 15px;
    }

    /* Estilos para alertas */
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.2);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: #2ecc71;
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.2);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: #e74c3c;
    }
    </style>
</body>
</html>

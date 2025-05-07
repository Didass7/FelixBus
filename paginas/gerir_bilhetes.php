<?php
session_start();
include '../basedados/basedados.h';

/**
 * Sistema de Gestão de Bilhetes
 *
 * Este ficheiro permite que funcionários e administradores comprem bilhetes para clientes.
 *
 * @author FelixBus
 * @version 1.0
 */

// Verificar permissões de acesso
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

// Inicializar variáveis
$mensagem = '';
$erro = '';
$resultados = [];
$origens = [];
$destinos = [];
$cliente_info = [];

// Obter mensagem da sessão, se existir
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

// Obter lista de clientes
$sql_clientes = "SELECT u.id_utilizador, u.nome_completo, u.email, c.saldo
                FROM utilizadores u
                LEFT JOIN carteiras c ON u.id_utilizador = c.id_utilizador
                WHERE u.perfil = 'cliente'
                ORDER BY u.nome_completo ASC";
$result_clientes = mysqli_query($conn, $sql_clientes);

// Processar seleção de cliente
if (isset($_GET['id_cliente'])) {
    $id_cliente = $_GET['id_cliente'];

    // Obter informações do cliente selecionado
    $stmt = mysqli_prepare($conn, "SELECT nome_completo, email FROM utilizadores WHERE id_utilizador = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $cliente_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Obter origens disponíveis
    $result_origens = mysqli_query($conn, "SELECT DISTINCT origem FROM rotas WHERE origem IS NOT NULL ORDER BY origem");
    while ($row = mysqli_fetch_assoc($result_origens)) {
        $origens[] = $row['origem'];
    }

    // Obter destinos disponíveis
    $result_destinos = mysqli_query($conn, "SELECT DISTINCT destino FROM rotas WHERE destino IS NOT NULL ORDER BY destino");
    while ($row = mysqli_fetch_assoc($result_destinos)) {
        $destinos[] = $row['destino'];
    }

    // Definir parâmetros de pesquisa
    $origem = trim($_GET['origem'] ?? '');
    $destino = trim($_GET['destino'] ?? '');
    $data_viagem = $_GET['data_viagem'] ?? date('Y-m-d');

    // Garantir que existam registos de viagens diárias para todos os horários na data selecionada
    criarRegistosViagensSeNecessario($conn, $data_viagem);

    // Processar pesquisa de rotas
    if (isset($_GET['pesquisar'])) {
        $resultados = pesquisarRotas($conn, $data_viagem, $origem, $destino);
    }
}

/**
 * Cria registos de viagens diárias para todos os horários na data especificada, se não existirem
 *
 * @param mysqli $conn Conexão com a base de dados
 * @param string $data_viagem Data da viagem no formato Y-m-d
 */
function criarRegistosViagensSeNecessario($conn, $data_viagem) {
    // Obter todos os horários disponíveis
    $result_horarios = mysqli_query($conn, "SELECT id_horario, lugares_disponiveis FROM horarios");
    $todos_horarios = [];
    while ($horario = mysqli_fetch_assoc($result_horarios)) {
        $todos_horarios[$horario['id_horario']] = $horario['lugares_disponiveis'];
    }

    // Verificar quais horários já têm registos para a data selecionada
    $stmt = mysqli_prepare($conn, "SELECT id_horario FROM viagens_diarias WHERE data_viagem = ?");
    mysqli_stmt_bind_param($stmt, "s", $data_viagem);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $horarios_existentes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $horarios_existentes[] = $row['id_horario'];
    }

    // Criar registos para horários que ainda não existem na data selecionada
    foreach ($todos_horarios as $id_horario => $lugares_disponiveis) {
        if (!in_array($id_horario, $horarios_existentes)) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO viagens_diarias (id_horario, data_viagem, lugares_disponiveis) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isi", $id_horario, $data_viagem, $lugares_disponiveis);
            mysqli_stmt_execute($stmt);
        }
    }
}

/**
 * Pesquisa rotas disponíveis com base nos critérios fornecidos
 *
 * @param mysqli $conn Conexão com a base de dados
 * @param string $data_viagem Data da viagem
 * @param string $origem Cidade de origem (opcional)
 * @param string $destino Cidade de destino (opcional)
 * @return array Array associativo com os resultados da pesquisa
 */
function pesquisarRotas($conn, $data_viagem, $origem, $destino) {
    $sql = "SELECT h.id_horario, r.origem, r.destino,
               TIME(h.hora_partida) as hora_partida,
               TIME(h.hora_chegada) as hora_chegada,
               h.preco,
               vd.lugares_disponiveis as lugares_disponiveis_data
            FROM horarios h
            JOIN rotas r ON h.id_rota = r.id_rota
            JOIN viagens_diarias vd ON h.id_horario = vd.id_horario AND vd.data_viagem = ?
            WHERE 1=1";

    // Preparar parâmetros
    $params = [$data_viagem];
    $types = "s";

    // Adicionar filtros se fornecidos
    if (!empty($origem)) {
        $sql .= " AND r.origem = ?";
        $params[] = $origem;
        $types .= "s";
    }

    if (!empty($destino)) {
        $sql .= " AND r.destino = ?";
        $params[] = $destino;
        $types .= "s";
    }

    $sql .= " ORDER BY h.hora_partida ASC";

    // Executar consulta
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (!mysqli_stmt_execute($stmt)) {
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Gestão de Bilhetes</title>
    <link rel="stylesheet" href="consultar_rotas.css">
    <link rel="stylesheet" href="gerir_bilhetes.css">
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
                    <a href="logout.php" class="nav-link">Sair</a>
                <?php elseif ($_SESSION['perfil'] === 'funcionário'): ?>
                    <a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                <?php elseif ($_SESSION['perfil'] === 'administrador'): ?>
                    <a href="pagina_inicial_admin.php" class="nav-link">Painel de Administração</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Sair</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="empresa.php" class="nav-link">Sobre Nós</a>
                <a href="register.php" class="nav-link">Registar</a>
                <a href="login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
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
</body>
</html>

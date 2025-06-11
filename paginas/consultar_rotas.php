<?php
session_start();
include '../basedados/basedados.h';

// inicializa variáveis para o formulário de pesquisa
$origem = trim($_GET['origem'] ?? '');
$destino = trim($_GET['destino'] ?? '');
$data_viagem = $_GET['data_viagem'] ?? date('Y-m-d');
$resultados = [];

// verifica a ligação à base de dados
if (!$conn) {
    die("Erro na ligação à base de dados: " . mysqli_connect_error());
}

// obtém todas as origens distintas para o menu de seleção
$sql_origens = "SELECT DISTINCT origem FROM rotas WHERE origem IS NOT NULL ORDER BY origem";
$stmt_origens = $conn->prepare($sql_origens);

if (!$stmt_origens) {
    die("Erro ao preparar consulta de origens: " . mysqli_error($conn));
}

$stmt_origens->execute();
$result_origens = $stmt_origens->get_result();

if (!$result_origens) {
    die("Erro ao obter origens: {$stmt_origens->error}");
}

// armazena as origens num array
$origens = [];
while ($row = $result_origens->fetch_assoc()) {
    $origens[] = $row['origem'];
}
$stmt_origens->close();

// obtém todos os destinos distintos para o menu de seleção
$sql_destinos = "SELECT DISTINCT destino FROM rotas WHERE destino IS NOT NULL ORDER BY destino";
$stmt_destinos = $conn->prepare($sql_destinos);

if (!$stmt_destinos) {
    die("Erro ao preparar consulta de destinos: " . mysqli_error($conn));
}

$stmt_destinos->execute();
$result_destinos = $stmt_destinos->get_result();

if (!$result_destinos) {
    die("Erro ao obter destinos: {$stmt_destinos->error}");
}

// armazena os destinos num array
$destinos = [];
while ($row = $result_destinos->fetch_assoc()) {
    $destinos[] = $row['destino'];
}
$stmt_destinos->close();

// pesquisa viagens disponíveis quando o utilizador clica em "Pesquisar"
if (isset($_GET['pesquisar'])) {
    // consulta para obter rotas, horários e lugares disponíveis para a data selecionada
    $sql = "SELECT r.id_rota, r.origem, r.destino, h.id_horario,
            h.hora_partida, h.hora_chegada, h.preco, h.lugares_disponiveis,
            COALESCE(vd.lugares_disponiveis, h.lugares_disponiveis) as lugares_disponiveis_data
            FROM rotas r
            JOIN horarios h ON r.id_rota = h.id_rota
            LEFT JOIN viagens_diarias vd ON h.id_horario = vd.id_horario
                AND vd.data_viagem = ?
            WHERE 1=1";

    // prepara parâmetros para a consulta parametrizada
    $params = [$data_viagem];
    $types = "s"; // tipo string para a data

    // adiciona filtros de origem e destino se fornecidos
    if (!empty($origem) || !empty($destino)) {
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
    }

    // ordena resultados por hora de partida
    $sql .= " ORDER BY h.hora_partida ASC";

    // prepara a consulta
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro na preparação da consulta: " . mysqli_error($conn));
    }

    // associa parâmetros à consulta
    $stmt->bind_param($types, ...$params);

    // executa a consulta
    if (!$stmt->execute()) {
        die("Erro na execução da consulta: {$stmt->error}");
    }

    // obtém resultados
    $result = $stmt->get_result();

    if (!$result) {
        die("Erro ao obter resultados: {$stmt->error}");
    }

    // armazena todos os resultados num array associativo
    $resultados = $result->fetch_all(MYSQLI_ASSOC);

    // fecha o statement
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Consultar Rotas e Horários</title>
    <link rel="stylesheet" href="consultar_rotas.css">
</head>
<body>
    <!-- Barra de Navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <?php if (isset($_SESSION['id_utilizador'])): ?>
                <?php
                // Menu para utilizadores autenticados com base no perfil
                if ($_SESSION['perfil'] === 'cliente'): ?>
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
                <!-- Menu para visitantes: ligações ordenadas por relevância -->
                <a href="index.php" class="nav-link">Início</a>
                <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
                <a href="empresa.php" class="nav-link">Sobre Nós</a>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Registar</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Secção de Cabeçalho e Pesquisa -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Consultar Rotas e Horários</h1>
            <p class="hero-subtitle">Encontre as melhores opções para a sua viagem</p>

            <!-- Formulário de Pesquisa de Viagens -->
            <form class="search-form" method="GET" action="consultar_rotas.php">
                <!-- Seleção da Origem -->
                <div class="form-group">
                    <label for="origem">Origem</label>
                    <select class="form-input" name="origem" id="origem">
                        <option value="">Todas as origens</option>
                        <?php foreach($origens as $cidade): ?>
                            <option value="<?php echo $cidade; ?>"
                                    <?php echo ($cidade === $origem) ? 'selected' : ''; ?>>
                                <?php echo $cidade; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Seleção do Destino -->
                <div class="form-group">
                    <label for="destino">Destino</label>
                    <select class="form-input" name="destino" id="destino">
                        <option value="">Todos os destinos</option>
                        <?php foreach($destinos as $cidade): ?>
                            <option value="<?php echo $cidade; ?>"
                                    <?php echo ($cidade === $destino) ? 'selected' : ''; ?>>
                                <?php echo $cidade; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Seleção da Data -->
                <div class="form-group">
                    <label for="data_viagem">Data da Viagem</label>
                    <input type="date" class="form-input" name="data_viagem" id="data_viagem"
                           value="<?php echo $data_viagem; ?>"
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                           required>
                </div>

                <!-- Botão de Pesquisa -->
                <button class="btn-primary" name="pesquisar" type="submit">Pesquisar</button>
            </form>
        </div>
    </section>

    <!-- Secção de Resultados da Pesquisa -->
    <section class="results-section">
        <div class="container">
            <?php if (!empty($resultados)): ?>
                <h2 class="section-title">Resultados da Pesquisa</h2>
                <div class="results-container">
                    <!-- Tabela com os resultados das viagens disponíveis -->
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Hora Partida</th>
                                <th>Hora Chegada</th>
                                <th>Preço</th>
                                <th>Lugares Disponíveis</th>
                                <?php if(isset($_SESSION['id_utilizador'])): ?>
                                <th>Ação</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($resultados as $rota): ?>
                                <tr>
                                    <td><?php echo $rota['origem']; ?></td>
                                    <td><?php echo $rota['destino']; ?></td>
                                    <td><?php echo date('H:i', strtotime($rota['hora_partida'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($rota['hora_chegada'])); ?></td>
                                    <td><?php echo number_format($rota['preco'], 2, ',', '.') . ' €'; ?></td>
                                    <td><?php echo $rota['lugares_disponiveis_data']; ?></td>

                                    <!-- Botão de compra apenas para clientes autenticados -->
                                    <?php if(isset($_SESSION['id_utilizador']) && $_SESSION['perfil'] == 'cliente'): ?>
                                    <td>
                                        <?php if($rota['lugares_disponiveis_data'] > 0): ?>
                                            <a href="comprar_bilhete.php?id_horario=<?php echo $rota['id_horario']; ?>&data_viagem=<?php echo urlencode($data_viagem); ?>"
                                               class="btn-action">Comprar</a>
                                        <?php else: ?>
                                            <span class="esgotado">Esgotado</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Mensagem quando não há resultados -->
                <div class="no-results">
                    <h2>Nenhum resultado encontrado</h2>
                    <p>Tente outra combinação de origem e destino.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Rodapé da Página -->
    <footer class="footer">
        <!-- Ligações para redes sociais -->
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>

        <!-- Ligações para páginas informativas -->
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <!-- Informação de direitos de autor -->
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

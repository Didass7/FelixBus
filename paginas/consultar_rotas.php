<?php
session_start();
include '../basedados/basedados.h';

// Inicializar variáveis
$origem = isset($_GET['origem']) ? trim($_GET['origem']) : '';
$destino = isset($_GET['destino']) ? trim($_GET['destino']) : '';
$resultados = [];

// Verificar a conexão com a base de dados
if (!$conn) {
    die("Erro na conexão com a base de dados: " . mysqli_connect_error());
}

// Buscar todas as origens e destinos distintos para os dropdowns
$sql_origens = "SELECT DISTINCT origem FROM rotas WHERE origem IS NOT NULL ORDER BY origem";
$result_origens = mysqli_query($conn, $sql_origens);

if (!$result_origens) {
    die("Erro ao buscar origens: " . mysqli_error($conn));
}

$origens = [];
while ($row = mysqli_fetch_assoc($result_origens)) {
    $origens[] = $row['origem'];
}

$sql_destinos = "SELECT DISTINCT destino FROM rotas WHERE destino IS NOT NULL ORDER BY destino";
$result_destinos = mysqli_query($conn, $sql_destinos);

if (!$result_destinos) {
    die("Erro ao buscar destinos: " . mysqli_error($conn));
}

$destinos = [];
while ($row = mysqli_fetch_assoc($result_destinos)) {
    $destinos[] = $row['destino'];
}

// Buscar resultados se houver pesquisa
if (isset($_GET['pesquisar'])) {
    $sql = "SELECT r.id_rota, r.origem, r.destino, h.id_horario, 
            h.hora_partida, h.hora_chegada, h.preco, h.lugares_disponiveis
            FROM rotas r
            JOIN horarios h ON r.id_rota = h.id_rota";
    
    // Inicializar arrays e variáveis
    $params = [];
    $types = "";
    
    if (!empty($origem) || !empty($destino)) {
        $sql .= " WHERE 1=1";
        
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
    
    $sql .= " ORDER BY h.hora_partida ASC";
    
    // Debug - Imprimir a consulta SQL
    echo "<!-- SQL Query: " . $sql . " -->";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        die("Erro na execução da consulta: " . mysqli_error($conn));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        die("Erro ao obter resultados: " . mysqli_error($conn));
    }
    
    $resultados = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Debug - Imprimir número de resultados
    echo "<!-- Número de resultados: " . count($resultados) . " -->";
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
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <?php if (isset($_SESSION['id_utilizador'])): ?>
                <?php if ($_SESSION['perfil'] === 'cliente'): ?>
                    <a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>
                    <a href="carteira.php" class="nav-link">Carteira</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php elseif ($_SESSION['perfil'] === 'funcionário'): ?>
                    <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
                    <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php elseif ($_SESSION['perfil'] === 'administrador'): ?>
                    <a href="gerir_rotas.php" class="nav-link">Gerir Rotas</a>
                    <a href="gerir_utilizadores.php" class="nav-link">Gerir Utilizadores</a>
                    <a href="gerir_alertas.php" class="nav-link">Gerir Alertas</a>
                    <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
                    <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Consultar Rotas e Horários</h1>
            <p class="hero-subtitle">Encontre as melhores opções para a sua viagem</p>
            
            <!-- Search Form -->
            <form class="search-form" method="GET" action="consultar_rotas.php">
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
                <button class="btn-primary" name="pesquisar" type="submit">Pesquisar</button>
            </form>
        </div>
    </section>

    <!-- Results Section -->
    <section class="results-section">
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
                                <?php if(isset($_SESSION['id_utilizador'])): ?>
                                <th>Ação</th>
                                <?php endif; ?>
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
                                    <td><?php echo $rota['lugares_disponiveis']; ?></td>
                                    <?php if(isset($_SESSION['id_utilizador']) && $_SESSION['perfil'] == 'cliente'): ?>
                                    <td>
                                        <a href="comprar_bilhete.php?id_horario=<?php echo $rota['id_horario']; ?>" class="btn-action">Comprar</a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h2>Nenhum resultado encontrado</h2>
                    <p>Tente outra combinação de origem e destino.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>
        
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>
        
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>




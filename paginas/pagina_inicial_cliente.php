<?php
session_start();

include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'cliente')) {
    header("Location: login.php");
    exit();
}

// Verificar se os parâmetros de pesquisa foram enviados
if (isset($_GET['origem']) || isset($_GET['destino'])) {
    $origem = isset($_GET['origem']) ? trim($_GET['origem']) : '';
    $destino = isset($_GET['destino']) ? trim($_GET['destino']) : '';

    // Conectar ao banco de dados
    if (!$conn) {
        die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
    }

    // Consulta para buscar rotas com base na origem e destino
    $sql = "SELECT r.id_rota, r.origem, r.destino, h.hora_partida, h.hora_chegada, h.preco, h.lugares_disponiveis 
            FROM rotas r
            JOIN horarios h ON r.id_rota = h.id_rota
            WHERE (? = '' OR r.origem LIKE CONCAT('%', ?, '%'))
            AND (? = '' OR r.destino LIKE CONCAT('%', ?, '%'))
            ORDER BY h.hora_partida ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $origem, $origem, $destino, $destino);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Exibir os resultados
    if (mysqli_num_rows($result) > 0) {
        echo "<div class='search-results'>";
        echo "<h2>Resultados da Pesquisa:</h2>";
        echo "<table class='results-table'>";
        echo "<tr><th>Origem</th><th>Destino</th><th>Hora de Partida</th><th>Hora de Chegada</th><th>Preço</th><th>Lugares Disponíveis</th><th>Ações</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['origem']) . "</td>";
            echo "<td>" . htmlspecialchars($row['destino']) . "</td>";
            echo "<td>" . htmlspecialchars($row['hora_partida']) . "</td>";
            echo "<td>" . htmlspecialchars($row['hora_chegada']) . "</td>";
            echo "<td>€" . htmlspecialchars($row['preco']) . "</td>";
            echo "<td>" . htmlspecialchars($row['lugares_disponiveis']) . "</td>";
            echo "<td><a href='reservar_viagem.php?id_rota=" . $row['id_rota'] . "' class='btn-reservar'>Reservar</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p class='no-results'>Nenhuma rota encontrada para os critérios informados.</p>";
    }

    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Viagens Premium</title>
    <link rel="stylesheet" href="pagina_inicial_cliente.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_cliente.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>
            <a href="carteira.php" class="nav-link">Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Viagens de Luxo Reimaginadas</h1>
            <p class="hero-subtitle">Conforto excepcional a preços acessíveis</p>
            
            <!-- Search Form -->
            <form class="search-form" method="GET" action="pagina_inicial_cliente.php">
                <input type="text" class="form-input" name="origem" placeholder="Origem">
                <input type="text" class="form-input" name="destino" placeholder="Destino">
                <button class="btn-primary" type="submit">Pesquisar Viagens</button>
            </form>
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

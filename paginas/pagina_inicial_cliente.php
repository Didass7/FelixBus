<?php
session_start();

include '../basedados/basedados.h'; // Inclui o arquivo diretamente

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'cliente')) {
    header("Location: login.php");
    exit();
}

// Verificar se os parâmetros de pesquisa foram enviados
if (isset($_GET['origem']) && isset($_GET['destino'])) {
    $origem = trim($_GET['origem']);
    $destino = trim($_GET['destino']);

    // Conectar ao banco de dados
    if (!$conn) {
        die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
    }

    // Consulta para buscar rotas com base na origem e destino
    $sql = "SELECT r.id_rota, r.origem, r.destino, h.hora_partida, h.hora_chegada, h.preco 
            FROM rotas r
            JOIN horarios h ON r.id_rota = h.id_rota
            WHERE r.origem LIKE ? AND r.destino LIKE ?";
    $stmt = mysqli_prepare($conn, $sql);
    $origem_param = '%' . $origem . '%';
    $destino_param = '%' . $destino . '%';
    mysqli_stmt_bind_param($stmt, "ss", $origem_param, $destino_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Exibir os resultados
    if (mysqli_num_rows($result) > 0) {
        echo "<h2>Resultados da Pesquisa:</h2>";
        echo "<table>";
        echo "<tr><th>Origem</th><th>Destino</th><th>Hora de Partida</th><th>Hora de Chegada</th><th>Preço</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['origem']) . "</td>";
            echo "<td>" . htmlspecialchars($row['destino']) . "</td>";
            echo "<td>" . htmlspecialchars($row['hora_partida']) . "</td>";
            echo "<td>" . htmlspecialchars($row['hora_chegada']) . "</td>";
            echo "<td>€" . htmlspecialchars($row['preco']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma rota encontrada para os critérios informados.</p>";
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
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_cliente.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="#rotas" class="nav-link">Rotas</a>
            <a href="#horarios" class="nav-link">Horários</a>
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
                <input type="text" class="form-input" name="origem" placeholder="Origem" required>
                <input type="text" class="form-input" name="destino" placeholder="Destino" required>
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
            <a href="#" class="footer-link">Sobre Nós</a>
            <a href="#" class="footer-link">Contactos</a>
            <a href="#" class="footer-link">Termos</a>
        </div>
        
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
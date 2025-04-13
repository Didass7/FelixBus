<?php
session_start();

include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'cliente')) {
    header("Location: login.php");
    exit();
}

// Buscar alertas ativos
$sql_alertas = "SELECT * FROM alertas WHERE ativo = 1 AND data_inicio <= NOW() AND data_fim >= NOW() ORDER BY data_criacao DESC";
$result_alertas = mysqli_query($conn, $sql_alertas);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Viagens Premium</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Navegação -->
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

    <!-- Secção Principal -->
    <section class="hero">
        <div class="hero-container">
            <!-- Conteúdo Principal -->
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Viagens de Luxo Reimaginadas</h1>
                    <p class="hero-subtitle">Conforto excepcional a preços acessíveis</p>
                </div>
                
                <!-- Botão de Consulta -->
                <div class="action-buttons">
                    <button class="btn-primary" type="button" onclick="window.location.href='consultar_rotas.php'">Consultar Rotas e Horários</button>
                </div>
            </div>

            <!-- Secção de Alertas -->
            <?php if (mysqli_num_rows($result_alertas) > 0): ?>
            <div class="hero-alerts">
                <h2 class="alerts-title">Alertas e Promoções</h2>
                <div class="alerts-container">
                    <?php while ($alerta = mysqli_fetch_assoc($result_alertas)): ?>
                    <div class="alert-card">
                        <h3><?php echo htmlspecialchars($alerta['titulo']); ?></h3>
                        <p><?php echo htmlspecialchars($alerta['conteudo']); ?></p>
                        <div class="alert-date">
                            <small>Válido até: <?php echo date('d/m/Y', strtotime($alerta['data_fim'])); ?></small>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Rodapé -->
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

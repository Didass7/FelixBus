<?php
session_start();
include '../basedados/basedados.h'; // Inclui o ficheiro de conexão à base de dados

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
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="empresa.php" class="nav-link">Sobre Nós</a>
            <a href="register.php" class="nav-link">Registar</a>
            <a href="login.php" class="nav-link">Login</a>
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
                    <p class="login-subtitle">Crie uma conta ou faça Login para usufruir dos nossos serviços!</p>
                </div>
                
                <!-- Botões de Login e Registo -->
                <div class="login-button">
                    <button class="btn-primary" type="button" onclick="window.location.href='login.php'">Login</button>
                    <button class="btn-primary" type="button" onclick="window.location.href='register.php'">Registar</button>
                </div>
            </div>

            <!-- Secção de Alertas -->
            <?php if (mysqli_num_rows($result_alertas) > 0): ?>
            <div class="hero-alerts">
                <h2 class="alerts-title">Alertas e Promoções</h2>
                <div class="alerts-container">

                    <!-- Loop para iterar sobre a tabela de alertas -->
                    <?php while ($alerta = mysqli_fetch_assoc($result_alertas)): ?>
                    <div class="alert-card">

                        <!-- Conteúdo do Alerta -->
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

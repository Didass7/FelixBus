<?php
session_start();
include '../basedados/basedados.h';

// Buscar alertas ativos
$sql_alertas = "SELECT * FROM alertas WHERE ativo = 1 AND data_inicio <= NOW() AND data_fim >= NOW() ORDER BY data_criacao DESC";
$result_alertas = mysqli_query($conn, $sql_alertas);

// Verificar se existe mensagem na sessão
$mensagem = '';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpa a mensagem da sessão
}
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

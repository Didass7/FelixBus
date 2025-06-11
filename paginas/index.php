<?php
// inicia a sessão e inclui o ficheiro de ligação à base de dados
// obtém alertas ativos e verifica mensagens da sessão
session_start();
include '../basedados/basedados.h';

// Obter alertas ativos ordenados por data de criação
$sql_alertas = "SELECT * FROM alertas WHERE ativo = 1 AND data_inicio <= NOW() AND data_fim >= NOW() ORDER BY data_criacao DESC";
$result_alertas = mysqli_query($conn, $sql_alertas);

// Verificar e limpar mensagem da sessão
$mensagem = '';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
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
    <!-- barra de navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="<?php
                // determina a página inicial para o link do logo com base no perfil do utilizador
                $pagina_destino = 'index.php';
                if (isset($_SESSION['perfil'])) {
                    if ($_SESSION['perfil'] === 'cliente') {
                        $pagina_destino = 'pagina_inicial_cliente.php';
                    } elseif ($_SESSION['perfil'] === 'funcionário') {
                        $pagina_destino = 'pagina_inicial_funcionario.php';
                    } elseif ($_SESSION['perfil'] === 'administrador') {
                        $pagina_destino = 'pagina_inicial_admin.php';
                    }
                }
                echo $pagina_destino;
            ?>">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <?php if (isset($_SESSION['id_utilizador'])): ?>
                <?php
                // define os links de navegação para utilizadores autenticados, variando conforme o perfil
                if ($_SESSION['perfil'] === 'cliente'): ?>
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
                <!-- define os links de navegação para visitantes não autenticados -->
                <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
                <a href="empresa.php" class="nav-link">Sobre Nós</a>
                <a href="register.php" class="nav-link">Registar</a>
                <a href="login.php" class="nav-link">Login</a>
                <a href="index.php" class="nav-link">Início</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- secção principal da página -->
    <section class="hero">
        <div class="hero-container">
            <!-- conteúdo principal com texto e botões -->
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Viagens de Luxo Reimaginadas</h1>
                    <p class="hero-subtitle">Conforto excepcional a preços acessíveis</p>
                    <p class="login-subtitle">Crie uma conta ou faça Login para usufruir dos nossos serviços!</p>
                </div>

                <!-- botões para autenticação -->
                <div class="login-button">
                    <button class="btn-primary" type="button" onclick="window.location.href='login.php'">Login</button>
                    <button class="btn-primary" type="button" onclick="window.location.href='register.php'">Registar</button>
                </div>
            </div>

            <!-- secção de alertas -->
            <?php if (mysqli_num_rows($result_alertas) > 0): ?>
            <div class="hero-alerts">
                <h2 class="alerts-title">Alertas e Promoções</h2>
                <div class="alerts-container">
                    <?php
                    // apresenta cada alerta ativo recuperado da base de dados
                    while ($alerta = mysqli_fetch_assoc($result_alertas)):
                    ?>
                    <div class="alert-card">
                        <h3><?php echo $alerta['titulo']; ?></h3>
                        <p><?php echo $alerta['conteudo']; ?></p>
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

    <!-- rodapé da página -->
    <footer class="footer">
        <!-- ligações para redes sociais -->
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>

        <!-- ligações para páginas informativas -->
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <!-- informação de direitos de autor -->
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

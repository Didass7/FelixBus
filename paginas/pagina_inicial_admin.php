<?php
/**
 * Página Inicial do Administrador
 *
 * Esta página apresenta o painel de administração do sistema FelixBus,
 * permitindo acesso às várias funcionalidades de gestão.
 * 
 * Acesso ao ficheiro: apenas Administradores.
 */

// Inicia a sessão
session_start();

// Inclui o ficheiro de ligação à base de dados
include '../basedados/basedados.h';

// Verifica se o utilizador está autenticado e tem perfil de administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Administrador - FelixBus</title>
    <link rel="stylesheet" href="pagina_inicial_admin.css">
</head>
<body>
    <!-- Barra de navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_admin.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="pagina_inicial_admin.php" class="nav-link">Painel</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </nav>

    <!-- Conteúdo principal -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Painel de Administração</h1>
            <p class="hero-subtitle">Gerencie rotas, utilizadores e informações do sistema</p>

            <!-- Cartões de ações administrativas -->
            <div class="admin-actions">
                <!-- Gestão de Rotas -->
                <a href="gerir_rotas.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Rotas</h3>
                        <p>Rotas, horários e capacidade</p>
                    </div>
                </a>

                <!-- Gestão de Utilizadores -->
                <a href="gerir_utilizadores.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Utilizadores</h3>
                        <p>Contas de utilizadores e funcionários</p>
                    </div>
                </a>

                <!-- Gestão de Alertas -->
                <a href="gerir_alertas.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Alertas</h3>
                        <p>Alertas, informações e promoções</p>
                    </div>
                </a>

                <!-- Gestão de Carteiras -->
                <a href="gerir_carteiras.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Carteiras</h3>
                        <p>Saldo das carteiras dos clientes</p>
                    </div>
                </a>

                <!-- Gestão de Bilhetes -->
                <a href="gerir_bilhetes.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Bilhetes</h3>
                        <p>Compra e gestão de bilhetes</p>
                    </div>
                </a>

                <!-- Edição de Perfil -->
                <a href="perfil.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Edição de Perfil</h3>
                        <p>Edite o seu perfil</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Rodapé -->
    <footer class="footer">
        <!-- Redes sociais -->
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>

        <!-- Links úteis -->
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <!-- Direitos de autor -->
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

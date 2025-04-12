<?php
session_start();

include '../basedados/basedados.h';

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
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_admin.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="gerir_rotas.php" class="nav-link">Gerir Rotas</a>
            <a href="gerir_utilizadores.php" class="nav-link">Gerir Utilizadores</a>
            <a href="gerir_alertas.php" class="nav-link">Gerir Alertas</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Painel de Administração</h1>
            <p class="hero-subtitle">Gerencie rotas, utilizadores e informações do sistema</p>
            
            <div class="admin-actions">
                <a href="gerir_rotas.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Rotas</h3>
                        <p>Gerencie rotas, horários e capacidade dos autocarros</p>
                    </div>
                </a>

                <a href="gerir_utilizadores.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Utilizadores</h3>
                        <p>Administre contas de utilizadores, funcionários e administradores</p>
                    </div>
                </a>

                <a href="gerir_alertas.php" class="admin-action-card">
                    <div class="card-content">
                        <h3>Gestão de Alertas</h3>
                        <p>Gerencie alertas, informações e promoções do sistema</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

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

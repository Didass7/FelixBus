<?php
session_start();
include '../basedados/basedados.h';
// Ficheiro de apresentação da empresa FelixBus
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Sobre Nós</title>
    <link rel="stylesheet" href="empresa.css">
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

    <!-- Secção de Cabeçalho -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Sobre a FelixBus</h1>
            <p class="hero-subtitle">Conheça a nossa história e missão</p>
        </div>
    </section>

    <!-- Secção de Informações da Empresa -->
    <section class="company-info-section">
        <div class="container">
            <!-- Conteúdo sobre a história da empresa -->
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">Nossa História</h2>
                    <p>A FelixBus foi fundada em 2025 com a missão de revolucionar o transporte rodoviário de passageiros na Europa. Começamos com apenas 3 autocarros e hoje contamos com uma frota moderna de mais de 100 veículos que conectam as principais cidades europeias.</p>
                    <p>Nossa filosofia é oferecer viagens confortáveis a preços acessíveis, sem comprometer a qualidade do serviço. Investimos constantemente em tecnologia e treinamento para garantir a melhor experiência aos nossos passageiros.</p>
                </div>
                <div class="about-image">
                    <img src="about-image.jpg" alt="FelixBus História" onerror="this.src='logo.png'">
                </div>
            </div>

            <!-- Informações de contacto e localização -->
            <h2 class="section-title">Informações da Empresa</h2>
            <div class="info-cards">
                <!-- Cartão com informações de localização -->
                <div class="info-card" id="localizacao">
                    <h3>Localização</h3>
                    <p>Av. do Empresário, Castelo Branco</p>
                    <p>Portugal</p>
                    <p>Código Postal: 6000-767</p>
                </div>

                <!-- Cartão com horários de funcionamento -->
                <div class="info-card">
                    <h3>Horário de Funcionamento</h3>
                    <p>Segunda a Sexta: 08:00 - 20:00</p>
                    <p>Sábado: 09:00 - 18:00</p>
                    <p>Domingo: Fechado</p>
                </div>

                <!-- Cartão com informações de contacto -->
                <div class="info-card" id="contactos">
                    <h3>Contactos</h3>
                    <p>Telefone: +351 999 999 999</p>
                    <p>Email: info@felixbus.pt</p>
                    <p>Suporte: suporte@felixbus.pt</p>
                </div>
            </div>
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

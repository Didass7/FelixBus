<?php
session_start(); // inicia a sessão

include '../basedados/basedados.h'; // inclui a ligação à base de dados

// verifica se o utilizador está autenticado como funcionário
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário')) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Funcionário - FelixBus</title>
    <link rel="stylesheet" href="pagina_inicial_funcionario.css">
</head>
<body>
    <!-- barra de navegação com links principais -->
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_funcionario.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </nav>

    <!-- secção principal com funcionalidades de gestão -->
    <section class="hero">
        <div class="hero-content">
            <h1>Painel de Gestão</h1>
            <p class="hero-subtitle">Bem-vindo à área do funcionário da FelixBus</p>

            <div class="dashboard-cards">
                <a href="gerir_carteiras.php" class="dashboard-card">
                    <div class="card-content">
                        <h3>Gestão de Carteiras</h3>
                        <p>Gerir o saldo das carteiras dos clientes</p>
                    </div>
                </a>

                <a href="gerir_bilhetes.php" class="dashboard-card">
                    <div class="card-content">
                        <h3>Gestão de Bilhetes</h3>
                        <p>Comprar e gerir bilhetes para clientes</p>
                    </div>
                </a>

                <a href="perfil.php" class="dashboard-card">
                    <div class="card-content">
                        <h3>O Meu Perfil</h3>
                        <p>Visualizar e editar os seus dados pessoais</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- rodapé com links úteis -->
    <footer class="footer">
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

<?php
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
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
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_funcionario.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
            <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
            <a href="carteira.php" class="nav-link">Minha Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="dashboard">
        <h1>Painel de Gestão</h1>
        
        <div class="dashboard-cards">
            <a href="gerir_carteiras.php" class="dashboard-card">
                <div class="card-content">
                    <h3>Gestão de Carteiras</h3>
                    <p>Gerencie o saldo das carteiras dos clientes</p>
                </div>
            </a>

            <a href="gerir_bilhetes.php" class="dashboard-card">
                <div class="card-content">
                    <h3>Gestão de Bilhetes</h3>
                    <p>Compre e gerencie bilhetes para clientes</p>
                </div>
            </a>

            <a href="perfil.php" class="dashboard-card">
                <div class="card-content">
                    <h3>Meu Perfil</h3>
                    <p>Visualize e edite seus dados pessoais</p>
                </div>
            </a>
        </div>
    </main>

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

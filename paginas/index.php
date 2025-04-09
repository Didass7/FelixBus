<?php
session_start();

include '../basedados/basedados.h'; // Inclui o arquivo diretamente
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
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="register.php" class="nav-link">registar</a>
            <a href="login.php" class="nav-link">login</a>
            <a href="logout.php" class="nav-link">Logout</a>
            
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Viagens de Luxo Reimaginadas</h1>
            <p class="hero-subtitle">Conforto excepcional a preços acessíveis</p>
            <p class="login-subtitle">Crie uma conta ou faça Login para usufruir dos nossos serviços!</p>
            
            <!-- Login Form -->
            <form class="login-button">
                <button class="btn-primary" type="button" onclick="window.location.href='login.php'">Login</button>
                <button class="btn-primary" type="button" onclick="window.location.href='register.php'">Registar</button>
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
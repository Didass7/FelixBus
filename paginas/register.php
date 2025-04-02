<?php
session_start();

?>


<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Register</title>
    <link rel="stylesheet" href="register.css">
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
            <a href="#rotas" class="nav-link">Rotas</a>
            <a href="#horarios" class="nav-link">Horários</a>
            <a href="#login" class="nav-link">Login</a>
        </div>
    </nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        
        <div class="login-container">
            <?php if (isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <h2>Registo</h2>
            <form method="POST" action="processar_registo.php">
                <!-- Campo de Email -->
                <div class="email">
                    <div class="input-container">
                        <i class="fa fa-envelope" aria-hidden="true"></i>
                        <input type="email" id="email" name="email" 
                               placeholder="Insira o seu email" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>

                <!-- Campo de Password -->
                <div class="password">
                    <div class="input-container">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Cria uma password" required>
                    </div>
                </div>

                <!-- Campo de Confirmação de Password -->
                <div class="password">
                    <div class="input-container">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirma a password" required>
                    </div>
                </div>

                <button type="submit">Registar</button>
                
                <div class="login-link">
                    Já tem conta? <a href="login.php">Faça login aqui</a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .login-link {
        margin-top: 15px;
        text-align: center;
        font-size: 14px;
    }
    
    .login-link a {
        color: #007bff;
        text-decoration: none;
    }
    
    .login-link a:hover {
        text-decoration: underline;
    }
    
    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
</style>




    <!-- Novo Footer -->
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
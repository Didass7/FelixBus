<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Viagens Premium</title>
    <link rel="stylesheet" href="login.css">
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
                <form action="login.php" method="POST">

                    <div class="email">
                        <div class="input-container">
                            <i class="fa fa-user" aria-hidden="true"></i>
                            <input type="text" id="username" name="username" placeholder="Insira o nome de utilizador" required>
                        </div>
                    </div>

                    <div class="password">
                        <div class="input-container">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <input type="password" id="password" name="password" placeholder="Insira a password" required>
                        </div>
                    </div>

                    <button type="submit">Login</button>

                </form>
            </div>
        </div>
    </section>




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
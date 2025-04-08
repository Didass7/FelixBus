<?php
session_start();

require_once '../basedados/basedados.h'; // Inclui o arquivo diretamente

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'cliente' && $_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Viagens Premium</title>
    <link rel="stylesheet" href="perfil.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <a href="<?php 
                if ($_SESSION['perfil'] === 'cliente') {
                    echo 'pagina_inicial_cliente.php';
                } elseif ($_SESSION['perfil'] === 'funcionário') {
                    echo 'pagina_inicial_funcionario.php';
                } elseif ($_SESSION['perfil'] === 'administrador') {
                    echo 'pagina_inicial_admin.php';
                } else {
                    echo '#'; // Link padrão caso o perfil não seja reconhecido
                }
            ?>">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
        <div class="nav-links">
            <a href="#rotas" class="nav-link">Rotas</a>
            <a href="#horarios" class="nav-link">Horários</a>
            <a href="carteira.php" class="nav-link">Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Bem-Vindo ao seu perfil,
                <span class="user-name">
                    <?php echo !empty($_SESSION['nome_completo']) ? htmlspecialchars($_SESSION['nome_completo']) : ''; ?>
                </span>
            </h1>
            <section class="user-info">
                <div class="user-info-container">
                    <h2>Informações do Usuário</h2>
                    <ul id="user-info-display">
                        <li><strong>Nome Completo:</strong> <?php echo htmlspecialchars($_SESSION['nome_completo'] ?? 'Não disponível'); ?></li>
                        <li><strong>Nome de Utilizador:</strong> <?php echo htmlspecialchars($_SESSION['nome_utilizador'] ?? 'Não disponível'); ?></li>
                        <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? 'Não disponível'); ?></li>
                        <li><strong>Telefone:</strong> <?php echo htmlspecialchars($_SESSION['telefone'] ?? 'Não disponível'); ?></li>
                        <li><strong>Morada:</strong> <?php echo htmlspecialchars($_SESSION['morada'] ?? 'Não disponível'); ?></li>
                    </ul>

                    <button id="edit-button">Editar</button>

                    <!-- Formulário de edição -->
                    <form method="POST" action="atualizar_perfil.php" id="edit-form" style="display: none;">
                        <ul>
                            <li>
                                <label for="nome_completo">Nome Completo:</label>
                                <input type="text" name="nome_completo" id="nome_completo" value="<?php echo htmlspecialchars($_SESSION['nome_completo'] ?? ''); ?>">
                            </li>
                            <li>
                                <label for="nome_utilizador">Nome de Utilizador:</label>
                                <input type="text" name="nome_utilizador" id="nome_utilizador" value="<?php echo htmlspecialchars($_SESSION['nome_utilizador'] ?? ''); ?>">
                            </li>
                            <li>
                                <label for="email">Email:</label>
                                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                            </li>
                            <li>
                                <label for="telefone">Telefone:</label>
                                <input type="text" name="telefone" id="telefone" value="<?php echo htmlspecialchars($_SESSION['telefone'] ?? ''); ?>">
                            </li>
                            <li>
                                <label for="morada">Morada:</label>
                                <input type="text" name="morada" id="morada" value="<?php echo htmlspecialchars($_SESSION['morada'] ?? ''); ?>">
                            </li>
                        </ul>
                        <button type="submit">Salvar</button>
                        <button type="button" id="cancel-button">Cancelar</button>
                    </form>
                </div>
            </section>
        </div>
    </section>

    <script>
        const editButton = document.getElementById('edit-button');
        const cancelButton = document.getElementById('cancel-button');
        const editForm = document.getElementById('edit-form');
        const userInfoDisplay = document.getElementById('user-info-display');

        editButton.addEventListener('click', () => {
            userInfoDisplay.style.display = 'none'; // Esconde as informações básicas
            editForm.style.display = 'block'; // Mostra o formulário de edição
        });

        cancelButton.addEventListener('click', () => {
            editForm.style.display = 'none'; // Esconde o formulário de edição
            userInfoDisplay.style.display = 'block'; // Mostra as informações básicas
        });
    </script>

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
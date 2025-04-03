<?php
session_start();

require_once '../basedados/basedados.h'; // Inclui o arquivo diretamente

// Verificar se o usuário já está logado
if (isset($_SESSION['id_utilizador'])) {
    // Redirecionar para a página inicial ou painel do usuário
    switch ($_SESSION['perfil']) {
        case 'administrador':
            header("Location: admin.php");
            break;
        case 'funcionário':
            header("Location: funcionario.php");
            break;
        case "cliente":
            header("Location: pagina_inicial_cliente.php");
            break;
        default:
            header("Location: login.php");
    }
    exit();
}

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validar inputs
    if (empty($username) || empty($password)) {
        $error = "Por favor, preencha todos os campos!";
    } else {
        // A variável $conn já está disponível após o include
        if (!$conn) {
            die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
        }

        // Consulta segura com prepared statements
        $sql = "SELECT * FROM utilizadores WHERE nome_utilizador = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verificar password MD5 (ajuste conforme sua implementação)
            if (md5($password) === $user['hash_password']) {
                // Regenerar ID da sessão para evitar roubo de sessão
                session_regenerate_id(true);

                // Armazenar informações do usuário na sessão
                $_SESSION['id_utilizador'] = $user['id_utilizador'];
                $_SESSION['nome_utilizador'] = $user['nome_utilizador'];
                $_SESSION['perfil'] = $user['perfil'];

                // Redirecionar conforme perfil
                switch ($user['perfil']) {
                    case 'administrador':
                        header("Location: admin.php");
                        break;
                    case 'funcionário':
                        header("Location: funcionario.php");
                        break;
                    default:
                        header("Location: pagina_inicial_cliente.php");
                }
                exit();
            } else {
                $error = "Credenciais inválidas!";
            }
        } else {
            $error = "Utilizador não encontrado!";
        }

        mysqli_close($conn);
    }
}
?>

<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Login</title>
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
            <a href="index.php" class="nav-link">Página Inicial</a>
            <a href="register.php" class="nav-link">Registar</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="login-container">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="email">
                        <div class="input-container">
                            <i class="fa fa-user" aria-hidden="true"></i>
                            <input type="text" id="username" name="username" 
                                   placeholder="Insira o nome de utilizador" required
                                   value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        </div>
                    </div>

                    <div class="password">
                        <div class="input-container">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <input type="password" id="password" name="password" 
                                   placeholder="Insira a password" required>
                        </div>
                    </div>

                    <button type="submit">Login</button>
                </form>
            </div>
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
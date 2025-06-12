<?php
session_start();
include '../basedados/basedados.h';

// verifica se o utilizador já está autenticado e redireciona para a página apropriada
if (isset($_SESSION['id_utilizador'])) {
    switch ($_SESSION['perfil']) {
        case 'administrador':
            header("Location: pagina_inicial_admin.php");
            break;
        case 'funcionário':
            header("Location: pagina_inicial_funcionario.php");
            break;
        case 'cliente':
            header("Location: pagina_inicial_cliente.php");
            break;
        default:
            header("Location: login.php");
    }
    exit();
}

// processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ''); // recolhe e limpa o nome de utilizador
    $password = $_POST['password'] ?? ''; // recolhe a palavra-passe

    if (empty($username) || empty($password)) {
        $error = "Por favor, preencha todos os campos!";
    } else {
        try {
            if (!$conn) {
                throw new Exception("Erro ao ligar à base de dados: " . mysqli_connect_error());
            }

            $sql = "SELECT * FROM utilizadores WHERE nome_utilizador = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                $validado = $user['validado'] ?? 1; // verifica se a conta está validada

                if ($user['perfil'] === 'cliente' && !$validado) {
                    $error = "A sua conta ainda está pendente de aprovação pelo administrador.";
                } elseif (md5($password) === $user['hash_password']) {
                    session_regenerate_id(true); // regenera o ID da sessão

                    // armazena informações do utilizador na sessão
                    $_SESSION['id_utilizador'] = $user['id_utilizador'];
                    $_SESSION['nome_utilizador'] = $user['nome_utilizador'];
                    $_SESSION['perfil'] = $user['perfil'];
                    $_SESSION['nome_completo'] = $user['nome_completo'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['telefone'] = $user['telefone'];
                    $_SESSION['morada'] = $user['morada'];
                    $_SESSION['data_registo'] = $user['data_registo'];

                    switch ($user['perfil']) {
                        case 'administrador':
                            header("Location: pagina_inicial_admin.php");
                            break;
                        case 'funcionário':
                            header("Location: pagina_inicial_funcionario.php");
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
        } catch (Exception $e) {
            $error = "Erro: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FelixBus</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- barra de navegação com links principais -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php" class="nav-link">Início</a>
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="empresa.php" class="nav-link">Sobre Nós</a>
            <a href="register.php" class="nav-link">Registar</a>
            <a href="login.php" class="nav-link">Login</a>
        </div>
    </nav>

    <!-- secção principal com formulário de login -->
    <section class="hero">
        <div class="hero-content">
            <div class="login-container">
                <!-- Mensagens de sucesso -->
                <?php if (isset($_GET['success']) && isset($_GET['pending'])): ?>
                    <div class="success-message">
                        Registo concluído com sucesso! Por favor, aguarde a validação do seu registo por um administrador.
                        Receberá acesso assim que a sua conta for aprovada.
                    </div>
                <?php elseif (isset($_GET['success'])): ?>
                    <div class="success-message">Registo concluído com sucesso! Faça login para continuar.</div>
                <?php endif; ?>

                <!-- Mensagens de erro -->
                <?php if (isset($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <h2>Login</h2>

                <!-- Formulário de login -->
                <form method="POST" action="login.php">
                    <!-- Campo de Nome de Utilizador -->
                    <div class="input-container">
                        <i class="fa fa-user"></i>
                        <input type="text" name="username"
                               placeholder="Insira o seu nome de utilizador"
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                               required>
                    </div>

                    <!-- Campo de Palavra-passe -->
                    <div class="input-container">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password"
                               placeholder="Insira a sua palavra-passe"
                               required>
                    </div>

                    <!-- Botão de submissão -->
                    <button type="submit">Entrar</button>

                    <!-- Link para registo -->
                    <div class="register-link">
                        Não tem conta? <a href="register.php">Registe-se aqui</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- rodapé com links úteis e redes sociais -->
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

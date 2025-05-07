<?php
/**
 * Página de Login - FelixBus
 *
 * Esta página permite aos utilizadores autenticarem-se no sistema.
 * Verifica as credenciais e redireciona para a página adequada conforme o perfil.
 *
 * @author FelixBus
 * @version 1.0
 */

// Inicia a sessão
session_start();

// Inclui o ficheiro de ligação à base de dados
include '../basedados/basedados.h';

/**
 * Verifica se o utilizador já está autenticado
 * Redireciona para a página apropriada conforme o perfil
 */
if (isset($_SESSION['id_utilizador'])) {
    // Define a página de destino com base no perfil
    $pagina_destino = match($_SESSION['perfil']) {
        'administrador' => 'pagina_inicial_admin.php',
        'funcionário' => 'pagina_inicial_funcionario.php',
        'cliente' => 'pagina_inicial_cliente.php',
        default => 'login.php',
    };

    // Redireciona para a página de destino
    header("Location: $pagina_destino");
    exit();
}

/**
 * Processa o formulário de login
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolhe e limpa os dados do formulário
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Valida os dados
    if (empty($username) || empty($password)) {
        $error = "Por favor, preencha todos os campos!";
    } else {
        try {
            // Verifica a ligação à base de dados
            if (!$conn) {
                throw new Exception("Erro ao ligar à base de dados: " . mysqli_connect_error());
            }

            // Prepara e executa a consulta para encontrar o utilizador
            $sql = "SELECT * FROM utilizadores WHERE nome_utilizador = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                // Verifica se a conta está validada (apenas para clientes)
                $validado = $user['validado'] ?? 1; // Assume validado se a coluna não existir

                if ($user['perfil'] === 'cliente' && !$validado) {
                    $error = "A sua conta ainda está pendente de aprovação pelo administrador.";
                }
                // Verifica a palavra-passe
                elseif (md5($password) === $user['hash_password']) {
                    // Regenera o ID da sessão para evitar roubo de sessão
                    session_regenerate_id(true);

                    // Armazena as informações do utilizador na sessão
                    $_SESSION['id_utilizador'] = $user['id_utilizador'];
                    $_SESSION['nome_utilizador'] = $user['nome_utilizador'];
                    $_SESSION['perfil'] = $user['perfil'];
                    $_SESSION['nome_completo'] = $user['nome_completo'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['telefone'] = $user['telefone'];
                    $_SESSION['morada'] = $user['morada'];
                    $_SESSION['data_registo'] = $user['data_registo'];

                    // Redireciona conforme o perfil
                    $pagina_destino = match($user['perfil']) {
                        'administrador' => 'pagina_inicial_admin.php',
                        'funcionário' => 'pagina_inicial_funcionario.php',
                        default => 'pagina_inicial_cliente.php',
                    };

                    header("Location: $pagina_destino");
                    exit();
                } else {
                    $error = "Credenciais inválidas!";
                }
            } else {
                $error = "Utilizador não encontrado!";
            }
        } catch (Exception $e) {
            $error = "Erro: " . $e->getMessage();
        } finally {
            // Fecha a ligação à base de dados
            if (isset($conn)) {
                mysqli_close($conn);
            }
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
    <!-- Barra de navegação -->
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

    <!-- Secção principal -->
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

    <!-- Rodapé -->
    <footer class="footer">
        <!-- Redes sociais -->
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>

        <!-- Links úteis -->
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <!-- Direitos de autor -->
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

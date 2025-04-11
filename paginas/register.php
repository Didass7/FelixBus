<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o usuário já está logado
if (isset($_SESSION['id_utilizador'])) {
    // Redirecionar para a página inicial ou painel do usuário
    switch ($_SESSION['perfil']) {
        case 'administrador':
            header("Location: pagina_inicial_admin.php");
            break;
        case 'funcionário':
            header("Location: pagina_inicial_funcionario.php");
            break;
        case "cliente":
            header("Location: pagina_inicial_cliente.php");
            break;
        default:
            header("Location: login.php");
    }
    exit();
}

$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : null;
$old_username = isset($_SESSION['old_username']) ? $_SESSION['old_username'] : '';
$old_nome_completo = isset($_SESSION['old_nome_completo']) ? $_SESSION['old_nome_completo'] : '';
$old_morada = isset($_SESSION['old_morada']) ? $_SESSION['old_morada'] : '';
$old_telefone = isset($_SESSION['old_telefone']) ? $_SESSION['old_telefone'] : '';

unset($_SESSION['register_error']);
unset($_SESSION['old_username']);
unset($_SESSION['old_nome_completo']);
unset($_SESSION['old_morada']);
unset($_SESSION['old_telefone']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nome_completo = trim($_POST['nome_completo']);
    $morada = trim($_POST['morada']);
    $telefone = trim($_POST['telefone']);

    // Validação
    $error = null;

    if (empty($username)) {
        $error = "Por favor, insira um nome de utilizador!";
    } elseif (empty($password)) {
        $error = "Por favor, insira uma password!";
    } elseif (strlen($password) < 3) {
        $error = "A password deve ter pelo menos 3 caracteres!";
    } elseif ($password !== $confirm_password) {
        $error = "As passwords não coincidem!";
    } elseif (empty($nome_completo)) {
        $error = "Por favor, insira o nome completo!";
    } elseif (empty($morada)) {
        $error = "Por favor, insira a morada!";
    } elseif (empty($telefone)) {
        $error = "Por favor, insira o número de telefone!";
    }

    if (!$error) {
        if (!$conn) {
            die("Erro ao conectar ao banco de dados: " . mysqli_connect_error());
        }
        
        // Verificar se nome de utilizador já existe
        $sql_check = "SELECT id_utilizador FROM utilizadores WHERE nome_utilizador = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "Este nome de utilizador já está registrado!";
        } else {
            $hashed_password = md5($password);
            $email = $username . '@example.com';
            
            $sql_insert = "INSERT INTO utilizadores (
                            email, 
                            hash_password, 
                            perfil, 
                            nome_utilizador, 
                            data_registo,
                            nome_completo,
                            telefone,
                            morada
                          ) VALUES (?, ?, 'cliente', ?, NOW(), ?, ?, ?)";
            
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param(
                $stmt_insert, 
                "ssssss", // 6 parâmetros (ajustado)
                $email, 
                $hashed_password, 
                $username,
                $nome_completo,
                $telefone,
                $morada
            );
            
            if (mysqli_stmt_execute($stmt_insert)) {
                // Registro bem-sucedido, redirecionar para a página de login
                $id_utilizador = mysqli_insert_id($conn);
                
                // Create a wallet for the new user
                $sql_create_wallet = "INSERT INTO carteiras (id_utilizador, tipo, saldo) VALUES (?, 'cliente', 0.00)";
                $stmt_wallet = mysqli_prepare($conn, $sql_create_wallet);
                mysqli_stmt_bind_param($stmt_wallet, "i", $id_utilizador);
                mysqli_stmt_execute($stmt_wallet);
                
                header("Location: login.php?success=1");
                exit();
            } else {
                die("Erro ao registrar: " . mysqli_error($conn));
            }
        }
        mysqli_close($conn);
    }
    
    // Persistir dados para repopular formulário
    $_SESSION['register_error'] = $error;
    $_SESSION['old_username'] = $username;
    $_SESSION['old_nome_completo'] = $nome_completo;
    $_SESSION['old_morada'] = $morada;
    $_SESSION['old_telefone'] = $telefone;
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Register</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <a href="index.php" class="nav-link">Início</a>
            <a href="login.php" class="nav-link">Login</a>
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
                <form method="POST" action="register.php">
                    <!-- Nome de Utilizador -->
                    <div class="input-container">
                        <i class="fa fa-user"></i>
                        <input type="text" name="username" placeholder="Nome de utilizador" required
                            value="<?= htmlspecialchars($old_username) ?>">
                    </div>

                    <!-- Nome Completo -->
                    <div class="input-container">
                        <i class="fa fa-id-card"></i>
                        <input type="text" name="nome_completo" placeholder="Nome completo" required
                            value="<?= htmlspecialchars($old_nome_completo) ?>">
                    </div>

                    <!-- Morada -->
                    <div class="input-container">
                        <i class="fa fa-home"></i>
                        <input type="text" name="morada" placeholder="Morada" required
                            value="<?= htmlspecialchars($old_morada) ?>">
                    </div>

                    <!-- Telefone -->
                    <div class="input-container">
                        <i class="fa fa-phone"></i>
                        <input type="tel" name="telefone" placeholder="Telefone" required
                            value="<?= htmlspecialchars($old_telefone) ?>">
                    </div>

                    <!-- Password -->
                    <div class="input-container">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>

                    <!-- Confirmar Password -->
                    <div class="input-container">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Confirmar Password" required>
                    </div>

                    <button type="submit">Registar</button>
                    
                    <div class="login-link">
                        Já tem conta? <a href="login.php">Faça login aqui</a>
                    </div>
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
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>
        
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

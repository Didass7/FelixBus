<?php
session_start();
$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : null;
$old_email = isset($_SESSION['old_email']) ? $_SESSION['old_email'] : '';
unset($_SESSION['register_error']);
unset($_SESSION['old_email']);


require_once '../basedados/basedados.h';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validação
    $error = null;
    
    if (empty($email)) {
        $error = "Por favor, insira um email!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato de email inválido!";
    } elseif (empty($password)) {
        $error = "Por favor, insira uma password!";
    } elseif (strlen($password) < 8) {
        $error = "A password deve ter pelo menos 8 caracteres!";
    } elseif ($password !== $confirm_password) {
        $error = "As passwords não coincidem!";
    }

    if (!$error) {
        $conn = conectarBD();
        
        // Verificar se email já existe
        $sql_check = "SELECT id_utilizador FROM utilizadores WHERE email = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "Este email já está registrado!";
        } else {
            // Criar hash da password (MD5 para compatibilidade com o login existente)
            $hashed_password = md5($password);
            
            // Inserir novo usuário
            $sql_insert = "INSERT INTO utilizadores (email, hash_password, perfil, nome_utilizador, data_registo) 
                          VALUES (?, ?, 'cliente', ?, NOW())";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            
            $username = strtok($email, '@'); // Cria nome de usuário a partir do email
            
            mysqli_stmt_bind_param($stmt_insert, "sss", $email, $hashed_password, $username);
            $result = mysqli_stmt_execute($stmt_insert);
            
            if ($result) {
                // Registro bem-sucedido
                $user_id = mysqli_insert_id($conn);
                
                $_SESSION['id_utilizador'] = $user_id;
                $_SESSION['nome_utilizador'] = $username;
                $_SESSION['perfil'] = 'cliente';
                $_SESSION['email'] = $email;
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Erro ao registrar. Por favor, tente novamente.";
            }
        }
        
        mysqli_close($conn);
    }
    
    // Se houver erro, voltar para a página de registro
    $_SESSION['register_error'] = $error;
    $_SESSION['old_email'] = $email;
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
                <a href="#rotas" class="nav-link">Rotas</a>
                <a href="#horarios" class="nav-link">Horários</a>
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
                    <form method="POST" action="processar_registo.php">
                        <!-- Campo de Email -->
                        <div class="input-container">
                            <i class="fa fa-envelope"></i>
                            <input type="email" name="email" placeholder="Insira o seu email" required
                                value="<?= htmlspecialchars($old_email) ?>">
                        </div>

                        <!-- Campo de Password -->
                        <div class="input-container">
                            <i class="fa fa-lock"></i>
                            <input type="password" name="password" placeholder="Cria uma password" required>
                        </div>

                        <!-- Campo de Confirmação de Password -->
                        <div class="input-container">
                            <i class="fa fa-lock"></i>
                            <input type="password" name="confirm_password" placeholder="Confirma a password" required>
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
                <a href="#" class="footer-link">Sobre Nós</a>
                <a href="#" class="footer-link">Contactos</a>
                <a href="#" class="footer-link">Termos</a>
            </div>
            
            <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>

        </footer>

    </body>

</html>
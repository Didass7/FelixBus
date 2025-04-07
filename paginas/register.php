<?php
session_start();
require_once '../basedados/basedados.h'; // Inclui o arquivo diretamente

$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : null;
$old_username = isset($_SESSION['old_username']) ? $_SESSION['old_username'] : '';
unset($_SESSION['register_error']);
unset($_SESSION['old_username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

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
    }

    if (!$error) {
        // Verificar se a conexão com o banco de dados foi estabelecida
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
            // Criar hash da password (MD5 para compatibilidade com o login existente)
            $hashed_password = md5($password);
            
            // Inserir novo usuário
            $sql_insert = "INSERT INTO utilizadores (email, hash_password, perfil, nome_utilizador, data_registo) 
                          VALUES (?, ?, 'cliente', ?, NOW())";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            
            $email = $username . '@example.com'; // Cria um email fictício
            
            mysqli_stmt_bind_param($stmt_insert, "sss", $email, $hashed_password, $username);
            $result = mysqli_stmt_execute($stmt_insert);
            
            if ($result) {
                echo "Registro bem-sucedido!";
                $user_id = mysqli_insert_id($conn);
                
                $_SESSION['id_utilizador'] = $user_id;
                $_SESSION['nome_utilizador'] = $username;
                $_SESSION['perfil'] = 'cliente';
                $_SESSION['email'] = $email;
                
                header("Location: index.php");
                exit();
            } else {
                die("Erro ao registrar: " . mysqli_error($conn));
            }
        }
        
        mysqli_close($conn);
    }
    
    // Se houver erro, voltar para a página de registro
    $_SESSION['register_error'] = $error;
    $_SESSION['old_username'] = $username;
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
                    <form method="POST" action="register.php">
                        <!-- Campo de Nome de Utilizador -->
                        <div class="input-container">
                            <i class="fa fa-user"></i>
                            <input type="text" name="username" placeholder="Insira o seu nome de utilizador" required
                                value="<?= htmlspecialchars($old_username) ?>">
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
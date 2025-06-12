<?php
session_start(); // inicia a sessão

include '../basedados/basedados.h'; // inclui a ligação à base de dados

// verifica se o utilizador já está autenticado
if (isset($_SESSION['id_utilizador'])) {
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

// recupera dados da sessão para repopular o formulário em caso de erro
$error = $_SESSION['register_error'] ?? null;
$old_username = $_SESSION['old_username'] ?? '';
$old_email = $_SESSION['old_email'] ?? '';
$old_nome_completo = $_SESSION['old_nome_completo'] ?? '';
$old_morada = $_SESSION['old_morada'] ?? '';
$old_telefone = $_SESSION['old_telefone'] ?? '';

// limpa variáveis de sessão após utilização
unset(
    $_SESSION['register_error'],
    $_SESSION['old_username'],
    $_SESSION['old_email'],
    $_SESSION['old_nome_completo'],
    $_SESSION['old_morada'],
    $_SESSION['old_telefone']
);

// processa o formulário quando submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nome_completo = trim($_POST['nome_completo']);
    $morada = trim($_POST['morada']);
    $telefone = trim($_POST['telefone']);

    // valida dados do formulário
    $campos = [
        'nome de utilizador' => $username,
        'email' => $email,
        'palavra-passe' => $password,
        'nome completo' => $nome_completo,
        'morada' => $morada,
        'número de telefone' => $telefone
    ];

    foreach ($campos as $campo => $valor) {
        if (empty($valor)) {
            $error = "Por favor, introduza o {$campo}.";
            break;
        }
    }

    if (!$error) {
        if (strlen($password) < 3) {
            $error = "A palavra-passe deve ter pelo menos 3 caracteres.";
        } elseif ($password !== $confirm_password) {
            $error = "As palavras-passe não coincidem.";
        }
    }

    // regista o utilizador se não houver erros
    if (!$error) {
        if (!$conn) {
            die("Erro na ligação à base de dados: " . mysqli_connect_error());
        }

        try {
            //Verifica se já existe um utilizador com o mesmo nome de utilizador
            $sql_check = "SELECT id_utilizador FROM utilizadores WHERE nome_utilizador = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                throw new Exception("Este nome de utilizador já está registado.");
            }

            $stmt_check->close();

            //converte a senha para um hash MD5
            $hashed_password = md5($password);

            //insere dados na tabela utilizadores
            $sql_insert = "INSERT INTO utilizadores
                          (email, hash_password, perfil, nome_utilizador, data_registo, nome_completo, telefone, morada)
                          VALUES (?, ?, 'cliente', ?, NOW(), ?, ?, ?)";

            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssssss",
                                  $email, $hashed_password, $username,
                                  $nome_completo, $telefone, $morada);

            if (!$stmt_insert->execute()) {
                throw new Exception("Erro ao registar utilizador");
            }

            $id_utilizador = $stmt_insert->insert_id;
            $stmt_insert->close();

            $sql_create_wallet = "INSERT INTO carteiras (id_utilizador, tipo, saldo) VALUES (?, 'cliente', 0.00)";
            $stmt_wallet = $conn->prepare($sql_create_wallet);
            $stmt_wallet->bind_param("i", $id_utilizador);

            if (!$stmt_wallet->execute()) {
                throw new Exception("Erro ao criar carteira");
            }

            $stmt_wallet->close();

            header("Location: login.php?success=1&pending=1");
            exit();

        } catch (Exception $e) {
            $error = $e->getMessage();
        } finally {
            $conn->close();
        }
    }

    if ($error) {
        $_SESSION['register_error'] = $error;
        $_SESSION['old_username'] = $username;
        $_SESSION['old_email'] = $email;
        $_SESSION['old_nome_completo'] = $nome_completo;
        $_SESSION['old_morada'] = $morada;
        $_SESSION['old_telefone'] = $telefone;

        header("Location: register.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Registo</title>
    <link rel="stylesheet" href="register.css">
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
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="empresa.php" class="nav-link">Sobre Nós</a>
            <a href="register.php" class="nav-link">Registar</a>
            <a href="login.php" class="nav-link">Login</a>
            <a href="index.php" class="nav-link">Início</a>
        </div>
    </nav>

    <!-- secção principal com formulário de registo -->
    <section class="hero">
        <div class="hero-content">
            <div class="login-container">
                <?php if (isset($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <h2>Registo de Nova Conta</h2>

                <form method="POST" action="register.php">
                    <div class="input-container">
                        <i class="fa fa-user"></i>
                        <input type="text" name="username"
                               placeholder="Nome de utilizador"
                               required
                               value="<?= htmlspecialchars($old_username) ?>">
                    </div>

                    <div class="input-container">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email"
                               placeholder="Email"
                               required
                               value="<?= htmlspecialchars($old_email ?? '') ?>">
                    </div>

                    <div class="input-container">
                        <i class="fa fa-id-card"></i>
                        <input type="text" name="nome_completo"
                               placeholder="Nome completo"
                               required
                               value="<?= htmlspecialchars($old_nome_completo) ?>">
                    </div>

                    <div class="input-container">
                        <i class="fa fa-home"></i>
                        <input type="text" name="morada"
                               placeholder="Morada"
                               required
                               value="<?= htmlspecialchars($old_morada) ?>">
                    </div>

                    <div class="input-container">
                        <i class="fa fa-phone"></i>
                        <input type="tel" name="telefone"
                               placeholder="Telefone"
                               required
                               value="<?= htmlspecialchars($old_telefone) ?>">
                    </div>

                    <div class="input-container">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password"
                               placeholder="Palavra-passe"
                               required>
                    </div>

                    <div class="input-container">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="confirm_password"
                               placeholder="Confirmar Palavra-passe"
                               required>
                    </div>

                    <button type="submit">Registar</button>

                    <div class="login-link">
                        Já tem conta? <a href="login.php">Inicie sessão aqui</a>
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

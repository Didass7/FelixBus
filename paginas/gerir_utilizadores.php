<?php
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$erro = '';

// Processar ações de gestão de utilizadores
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'criar':
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $nome_utilizador = trim($_POST['username']);
                $hash_password = md5($_POST['password']); // Changed to MD5
                $perfil = $_POST['perfil'];
                
                $sql = "INSERT INTO utilizadores (nome_completo, email, nome_utilizador, hash_password, perfil) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssss", $nome, $email, $nome_utilizador, $hash_password, $perfil);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador criado com sucesso!";
                } else {
                    $erro = "Erro ao criar utilizador: " . mysqli_error($conn);
                }
                break;

            case 'atualizar':
                $id = $_POST['id_utilizador'];
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $perfil = $_POST['perfil'];
                
                $sql = "UPDATE utilizadores SET nome_completo = ?, email = ?, perfil = ? WHERE id_utilizador = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $nome, $email, $perfil, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador atualizado com sucesso!";
                } else {
                    $erro = "Erro ao atualizar utilizador: " . mysqli_error($conn);
                }
                break;

            case 'desativar':
                $id = $_POST['id_utilizador'];
                $sql = "UPDATE utilizadores SET ativo = 0 WHERE id_utilizador = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador desativado com sucesso!";
                } else {
                    $erro = "Erro ao desativar utilizador: " . mysqli_error($conn);
                }
                break;

            case 'ativar':
                $id = $_POST['id_utilizador'];
                $sql = "UPDATE utilizadores SET ativo = 1 WHERE id_utilizador = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador ativado com sucesso!";
                } else {
                    $erro = "Erro ao ativar utilizador: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Buscar todos os utilizadores
$sql = "SELECT * FROM utilizadores ORDER BY data_registo DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores - FelixBus</title>
    <link rel="stylesheet" href="gerir_utilizadores.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_admin.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
        <?php if (isset($_SESSION['id_utilizador'])): ?>
                <?php if ($_SESSION['perfil'] === 'funcionário'): ?>
                    <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
                    <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php elseif ($_SESSION['perfil'] === 'administrador'): ?>
                    <a href="gerir_rotas.php" class="nav-link">Gerir Rotas</a>
                    <a href="gerir_utilizadores.php" class="nav-link">Gerir Utilizadores</a>
                    <a href="gerir_alertas.php" class="nav-link">Gerir Alertas</a>
                    <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
                    <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="empresa.php" class="nav-link">Sobre Nós</a>
                <a href="register.php" class="nav-link">Registar</a>
                <a href="login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <h1>Gestão de Utilizadores</h1>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <section class="criar-utilizador">
            <h2>Criar Novo Utilizador</h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="acao" value="criar">
                
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="perfil">Perfil</label>
                    <select id="perfil" name="perfil" required>
                        <option value="cliente">Cliente</option>
                        <option value="funcionário">Funcionário</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>

                <!-- Move button outside of grid -->
                <div class="button-container">
                    <button type="submit" class="btn-primary">Criar Utilizador</button>
                </div>
            </form>
        </section>

        <section class="lista-utilizadores">
            <h2>Utilizadores do Sistema</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nome_completo']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['nome_utilizador']); ?></td>
                                <td><?php echo htmlspecialchars($user['perfil']); ?></td>
                                <td><?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                                <td class="actions">
                                    <button onclick="editarUtilizador(<?php echo $user['id_utilizador']; ?>)" class="btn-edit">Editar</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="<?php echo $user['ativo'] ? 'desativar' : 'ativar'; ?>">
                                        <input type="hidden" name="id_utilizador" value="<?php echo $user['id_utilizador']; ?>">
                                        <button type="submit" 
                                                class="<?php echo $user['ativo'] ? 'btn-delete' : 'btn-activate'; ?>" 
                                                onclick="return confirm('Tem certeza que deseja <?php echo $user['ativo'] ? 'desativar' : 'ativar'; ?> este utilizador?')">
                                            <?php echo $user['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>

    <script>
        function editarUtilizador(id) {
            // Implementar lógica de edição via modal ou redirecionamento
            window.location.href = `editar_utilizador.php?id=${id}`;
        }
    </script>
</body>
</html>






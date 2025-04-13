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
            case 'inserir':
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $nome_utilizador = trim($_POST['username']);
                $hash_password = md5($_POST['password']);
                $perfil = $_POST['perfil'];
                $telefone = trim($_POST['telefone']);
                $morada = trim($_POST['morada']);
                
                $sql = "INSERT INTO utilizadores (nome_completo, email, nome_utilizador, hash_password, perfil, telefone, morada) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssss", $nome, $email, $nome_utilizador, $hash_password, $perfil, $telefone, $morada);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador inserido com sucesso!";
                } else {
                    $erro = "Erro ao inserir utilizador: " . mysqli_error($conn);
                }
                break;

            case 'editar':
                $id = $_POST['id_utilizador'];
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $perfil = $_POST['perfil'];
                $telefone = trim($_POST['telefone']);
                $morada = trim($_POST['morada']);
                
                // Se uma nova password foi fornecida
                if (!empty($_POST['password'])) {
                    $hash_password = md5($_POST['password']);
                    $sql = "UPDATE utilizadores 
                           SET nome_completo = ?, email = ?, perfil = ?, telefone = ?, morada = ?, hash_password = ? 
                           WHERE id_utilizador = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssssssi", $nome, $email, $perfil, $telefone, $morada, $hash_password, $id);
                } else {
                    $sql = "UPDATE utilizadores 
                           SET nome_completo = ?, email = ?, perfil = ?, telefone = ?, morada = ? 
                           WHERE id_utilizador = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssssi", $nome, $email, $perfil, $telefone, $morada, $id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador atualizado com sucesso!";
                } else {
                    $erro = "Erro ao atualizar utilizador: " . mysqli_error($conn);
                }
                break;

            case 'inativar':
                $id = $_POST['id_utilizador'];
                $sql = "UPDATE utilizadores SET ativo = 0 WHERE id_utilizador = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Utilizador inativado com sucesso!";
                } else {
                    $erro = "Erro ao inativar utilizador: " . mysqli_error($conn);
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

// Buscar utilizador específico para edição
$utilizador_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM utilizadores WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $utilizador_edicao = mysqli_fetch_assoc($result);
}

// Buscar todos os utilizadores para visualização
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
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- Formulário de Inserção/Edição -->
        <section class="form-section">
            <h2><?php echo $utilizador_edicao ? 'Editar Utilizador' : 'Inserir Novo Utilizador'; ?></h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="acao" value="<?php echo $utilizador_edicao ? 'editar' : 'inserir'; ?>">
                <?php if ($utilizador_edicao): ?>
                    <input type="hidden" name="id_utilizador" value="<?php echo $utilizador_edicao['id_utilizador']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required 
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['nome_completo']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           <?php echo $utilizador_edicao ? 'disabled' : 'required'; ?>
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['nome_utilizador']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password <?php echo $utilizador_edicao ? '(deixe em branco para manter)' : ''; ?></label>
                    <input type="password" id="password" name="password" <?php echo $utilizador_edicao ? '' : 'required'; ?>>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone"
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['telefone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="morada">Morada</label>
                    <textarea id="morada" name="morada"><?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['morada']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="perfil">Perfil</label>
                    <select id="perfil" name="perfil" required>
                        <option value="cliente" <?php echo ($utilizador_edicao && $utilizador_edicao['perfil'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                        <option value="funcionário" <?php echo ($utilizador_edicao && $utilizador_edicao['perfil'] === 'funcionário') ? 'selected' : ''; ?>>Funcionário</option>
                        <option value="administrador" <?php echo ($utilizador_edicao && $utilizador_edicao['perfil'] === 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><?php echo $utilizador_edicao ? 'Atualizar' : 'Inserir'; ?></button>
                    <?php if ($utilizador_edicao): ?>
                        <a href="gerir_utilizadores.php" class="btn-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Lista de Utilizadores -->
        <section class="lista-utilizadores">
            <h2>Visualizar Utilizadores</h2>
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
                                    <a href="?editar=<?php echo $user['id_utilizador']; ?>" class="btn-edit">Editar</a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="<?php echo $user['ativo'] ? 'inativar' : 'ativar'; ?>">
                                        <input type="hidden" name="id_utilizador" value="<?php echo $user['id_utilizador']; ?>">
                                        <button type="submit" 
                                                class="<?php echo $user['ativo'] ? 'btn-delete' : 'btn-activate'; ?>"
                                                onclick="return confirm('Tem certeza que deseja <?php echo $user['ativo'] ? 'inativar' : 'ativar'; ?> este utilizador?')">
                                            <?php echo $user['ativo'] ? 'Inativar' : 'Ativar'; ?>
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
</body>
</html>








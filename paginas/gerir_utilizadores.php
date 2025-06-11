<?php
// inicia a sessão
session_start();

// inclui o ficheiro de ligação à base de dados
include '../basedados/basedados.h';

// verifica se o utilizador está autenticado e tem perfil de administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

// inicializa variáveis de mensagens
$mensagem = '';
$erro = '';

// verifica se existe mensagem na sessão
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}

/**
 * processa as ações de gestão de utilizadores
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // obtém o ID do utilizador para ações que o requerem
    $id = $_POST['id_utilizador'] ?? null;

    switch ($acao) {
        case 'inserir':
            // recolhe e limpa os dados do formulário
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $nome_utilizador = trim($_POST['username']);
            $hash_password = md5($_POST['password']);
            $perfil = $_POST['perfil'];
            $telefone = trim($_POST['telefone']);
            $morada = trim($_POST['morada']);

            // verificar se já existe um utilizador com o mesmo nome de utilizador
            $sql_check_username = "SELECT id_utilizador FROM utilizadores WHERE nome_utilizador = ?";
            $stmt_check_username = $conn->prepare($sql_check_username);
            $stmt_check_username->bind_param("s", $nome_utilizador);
            $stmt_check_username->execute();
            $result_username = $stmt_check_username->get_result();

            if ($result_username->num_rows > 0) {
                $erro = "Erro: Já existe um utilizador com o nome de utilizador '$nome_utilizador'. Por favor, escolha outro nome de utilizador.";
                $stmt_check_username->close();
                break;
            }
            $stmt_check_username->close();

            // verificar se já existe um utilizador com o mesmo email
            $sql_check_email = "SELECT id_utilizador FROM utilizadores WHERE email = ?";
            $stmt_check_email = $conn->prepare($sql_check_email);
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $result_email = $stmt_check_email->get_result();

            if ($result_email->num_rows > 0) {
                $erro = "Erro: Já existe um utilizador com o email '$email'. Por favor, utilize outro endereço de email.";
                $stmt_check_email->close();
                break;
            }
            $stmt_check_email->close();

            // se não existem duplicados, proceder com a inserção
            $sql = "INSERT INTO utilizadores (nome_completo, email, nome_utilizador, hash_password, perfil, telefone, morada)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nome, $email, $nome_utilizador, $hash_password, $perfil, $telefone, $morada);

            if ($stmt->execute()) {
                $mensagem = "Utilizador inserido com sucesso!";
            } else {
                $erro = "Erro ao inserir utilizador: " . $stmt->error;
            }
            $stmt->close();
            break;

        case 'editar':
            // recolhe e limpa os dados do formulário
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $perfil = $_POST['perfil'];
            $telefone = trim($_POST['telefone']);
            $morada = trim($_POST['morada']);

            // verificar se já existe outro utilizador com o mesmo email (excluindo o utilizador atual)
            $sql_check_email = "SELECT id_utilizador FROM utilizadores WHERE email = ? AND id_utilizador != ?";
            $stmt_check_email = $conn->prepare($sql_check_email);
            $stmt_check_email->bind_param("si", $email, $id);
            $stmt_check_email->execute();
            $result_email = $stmt_check_email->get_result();

            if ($result_email->num_rows > 0) {
                $erro = "Erro: Já existe outro utilizador com o email '$email'. Por favor, utilize outro endereço de email.";
                $stmt_check_email->close();
                break;
            }
            $stmt_check_email->close();

            // verifica se foi fornecida uma nova password
            if (!empty($_POST['password'])) {
                $hash_password = md5($_POST['password']);
                $sql = "UPDATE utilizadores
                       SET nome_completo = ?, email = ?, perfil = ?, telefone = ?, morada = ?, hash_password = ?
                       WHERE id_utilizador = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $nome, $email, $perfil, $telefone, $morada, $hash_password, $id);
            } else {
                $sql = "UPDATE utilizadores
                       SET nome_completo = ?, email = ?, perfil = ?, telefone = ?, morada = ?
                       WHERE id_utilizador = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $nome, $email, $perfil, $telefone, $morada, $id);
            }

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Utilizador atualizado com sucesso!";
                header("Location: gerir_utilizadores.php#lista");
                exit();
            } else {
                $erro = "Erro ao atualizar utilizador: " . $stmt->error;
            }
            $stmt->close();
            break;

        case 'validar':
            // atualiza o estado de validação para 1 (validado)
            $sql = "UPDATE utilizadores SET validado = 1 WHERE id_utilizador = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Utilizador validado com sucesso!";
                header("Location: gerir_utilizadores.php#lista");
                exit();
            } else {
                $erro = "Erro ao validar utilizador: " . $stmt->error;
            }
            break;

        case 'rejeitar':
            // remove utilizador não validado
            $sql = "DELETE FROM utilizadores WHERE id_utilizador = ? AND validado = 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $mensagem = "Registo rejeitado com sucesso!";
            } else {
                $erro = "Erro ao rejeitar registo: " . $stmt->error;
            }
            break;

        case 'desvalidar':
            // atualiza o estado de validação para 0 (não validado)
            $sql = "UPDATE utilizadores SET validado = 0 WHERE id_utilizador = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $mensagem = "Utilizador desvalidado com sucesso!";
            } else {
                $erro = "Erro ao desvalidar utilizador: " . $stmt->error;
            }
            break;
    }
}

/**
 * obtém dados do utilizador para edição
 */
$utilizador_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM utilizadores WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $utilizador_edicao = $result->fetch_assoc();
}

/**
 * obtém lista de todos os utilizadores
 */
$sql = "SELECT * FROM utilizadores ORDER BY data_registo DESC";
$result = $conn->query($sql);
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
    <!-- Barra de navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_admin.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="pagina_inicial_admin.php" class="nav-link">Painel</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </nav>

    <!-- Conteúdo principal -->
    <main class="container">
        <!-- Mensagens de alerta -->
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <!-- Formulário de Inserção/Edição -->
        <section class="form-section">
            <h2><?php echo $utilizador_edicao ? 'Editar Utilizador' : 'Inserir Novo Utilizador'; ?></h2>
            <form method="POST" class="form-grid">
                <!-- Campos ocultos -->
                <input type="hidden" name="acao" value="<?php echo $utilizador_edicao ? 'editar' : 'inserir'; ?>">
                <?php if ($utilizador_edicao): ?>
                    <input type="hidden" name="id_utilizador" value="<?php echo $utilizador_edicao['id_utilizador']; ?>">
                <?php endif; ?>

                <!-- Dados pessoais -->
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

                <!-- Dados de acesso -->
                <div class="form-group">
                    <label for="username">Nome de Utilizador</label>
                    <input type="text" id="username" name="username"
                           <?php echo $utilizador_edicao ? 'disabled' : 'required'; ?>
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['nome_utilizador']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Palavra-passe <?php echo $utilizador_edicao ? '(deixe em branco para manter)' : ''; ?></label>
                    <input type="password" id="password" name="password" <?php echo $utilizador_edicao ? '' : 'required'; ?>>
                </div>

                <!-- Informações de contacto -->
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone"
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['telefone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="morada">Morada</label>
                    <input type="text" id="morada" name="morada"
                           value="<?php echo $utilizador_edicao ? htmlspecialchars($utilizador_edicao['morada']) : ''; ?>" required>
                </div>

                <!-- Tipo de perfil -->
                <div class="form-group">
                    <label for="perfil">Perfil</label>
                    <select id="perfil" name="perfil" required>
                        <option value="cliente" <?php echo ($utilizador_edicao && $utilizador_edicao['perfil'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                        <option value="funcionário" <?php echo ($utilizador_edicao && $utilizador_edicao['perfil'] === 'funcionário') ? 'selected' : ''; ?>>Funcionário</option>
                        <option value="administrador" <?php echo ($utilizador_edicao && $utilizador_edicao['perfil'] === 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>

                <!-- Botões de ação -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><?php echo $utilizador_edicao ? 'Atualizar' : 'Inserir'; ?></button>
                    <?php if ($utilizador_edicao): ?>
                        <a href="gerir_utilizadores.php" class="btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Lista de Utilizadores -->
        <section id="lista" class="lista-utilizadores">
            <h2>Lista de Utilizadores</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Perfil</th>
                            <th>Validado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nome_completo']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['nome_utilizador']); ?></td>
                                <td><?php echo htmlspecialchars($user['perfil']); ?></td>
                                <td><?php echo isset($user['validado']) ? ($user['validado'] ? 'Sim' : 'Não') : 'Não'; ?></td>
                                <td class="actions">
                                    <!-- Botão de edição -->
                                    <a href="?editar=<?php echo $user['id_utilizador']; ?>" class="btn-edit">Editar</a>

                                    <?php if ($user['perfil'] === 'cliente'): ?>
                                        <?php if (!isset($user['validado']) || !$user['validado']): ?>
                                            <!-- Botões para utilizadores não validados -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id_utilizador" value="<?php echo $user['id_utilizador']; ?>">
                                                <input type="hidden" name="acao" value="validar">
                                                <button type="submit" class="btn-approve">Validar</button>
                                            </form>

                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id_utilizador" value="<?php echo $user['id_utilizador']; ?>">
                                                <input type="hidden" name="acao" value="rejeitar">
                                                <button type="submit" class="btn-reject"
                                                        onclick="return confirm('Tem a certeza que deseja rejeitar este registo?')">
                                                    Rejeitar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Botão para utilizadores validados -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id_utilizador" value="<?php echo $user['id_utilizador']; ?>">
                                                <input type="hidden" name="acao" value="desvalidar">
                                                <button type="submit" class="btn-warning"
                                                        onclick="return confirm('Tem a certeza que deseja desvalidar este utilizador?')">
                                                    Desvalidar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="footer">
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

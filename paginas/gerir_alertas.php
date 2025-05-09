<?php
/**
 * Gestão de Alertas - FelixBus
 *
 * Este ficheiro permite aos administradores gerir alertas e promoções do sistema.
 * Funcionalidades:
 * - Criar novos alertas
 * - Editar alertas existentes
 * - Eliminar alertas
 * - Visualizar todos os alertas
 *
 * @author FelixBus
 * @version 1.0
 */

// Inicia a sessão
session_start();

// Inclui o ficheiro de ligação à base de dados
include '../basedados/basedados.h';

// Verifica se o utilizador está autenticado e tem perfil de administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

// Inicializa variáveis de mensagens
$mensagem = '';
$erro = '';

/**
 * Processa o formulário de criação/edição/eliminação de alertas
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se é uma ação de criação ou edição
    if (isset($_POST['acao'])) {
        // Recolhe e limpa os dados do formulário
        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        $data_inicio = $_POST['data_inicio'] ?? '';
        $data_fim = $_POST['data_fim'] ?? '';
        $id_admin = $_SESSION['id_utilizador'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Valida os dados
        if (empty($titulo) || empty($conteudo) || empty($data_inicio) || empty($data_fim)) {
            $erro = "Todos os campos são obrigatórios.";
        } else {
            try {
                // Verifica se as datas são válidas
                $data_inicio_obj = new DateTime($data_inicio);
                $data_fim_obj = new DateTime($data_fim);

                if ($data_fim_obj < $data_inicio_obj) {
                    $erro = "A data de fim deve ser posterior à data de início.";
                } else {
                    // Cria ou atualiza o alerta
                    if ($_POST['acao'] === 'criar') {
                        // Prepara e executa a consulta de inserção
                        $sql = "INSERT INTO alertas (titulo, conteudo, data_inicio, data_fim, criado_por, ativo)
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssii", $titulo, $conteudo, $data_inicio, $data_fim, $id_admin, $ativo);

                        if ($stmt->execute()) {
                            $mensagem = "Alerta criado com sucesso!";
                        } else {
                            $erro = "Erro ao criar alerta: {$stmt->error}";
                        }
                        $stmt->close();
                    } elseif ($_POST['acao'] === 'editar' && isset($_POST['id_alerta'])) {
                        // Prepara e executa a consulta de atualização
                        $id_alerta = $_POST['id_alerta'];
                        $sql = "UPDATE alertas SET titulo = ?, conteudo = ?, data_inicio = ?, data_fim = ?, ativo = ?
                                WHERE id_alerta = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssii", $titulo, $conteudo, $data_inicio, $data_fim, $ativo, $id_alerta);

                        if ($stmt->execute()) {
                            $mensagem = "Alerta atualizado com sucesso!";
                        } else {
                            $erro = "Erro ao atualizar alerta: {$stmt->error}";
                        }
                        $stmt->close();
                    }
                }
            } catch (Exception $e) {
                $erro = "Erro ao processar datas: {$e->getMessage()}";
            }
        }
    }
    // Verifica se é uma ação de eliminação
    elseif (isset($_POST['excluir']) && isset($_POST['id_alerta'])) {
        $id_alerta = $_POST['id_alerta'];

        // Prepara e executa a consulta de eliminação
        $sql = "DELETE FROM alertas WHERE id_alerta = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_alerta);

        if ($stmt->execute()) {
            $mensagem = "Alerta eliminado com sucesso!";
        } else {
            $erro = "Erro ao eliminar alerta: {$stmt->error}";
        }
        $stmt->close();
    }
}

/**
 * Obtém dados do alerta para edição
 */
$alerta_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_alerta = $_GET['editar'];

    // Prepara e executa a consulta
    $sql = "SELECT * FROM alertas WHERE id_alerta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_alerta);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $alerta_edicao = $row;
    }
    $stmt->close();
}

/**
 * Obtém todos os alertas para exibição na tabela
 */
$sql_alertas = "SELECT a.*, u.nome_completo as nome_admin
                FROM alertas a
                JOIN utilizadores u ON a.criado_por = u.id_utilizador
                ORDER BY a.data_criacao DESC";
$stmt_alertas = $conn->prepare($sql_alertas);
$stmt_alertas->execute();
$result_alertas = $stmt_alertas->get_result();
$stmt_alertas->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Alertas - FelixBus</title>
    <link rel="stylesheet" href="gerir_alertas.css">
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
    <div class="container">
        <h1 class="page-title">Gestão de Alertas e Promoções</h1>

        <!-- Mensagens de alerta -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de criação/edição -->
        <section class="form-section">
            <h2><?php echo $alerta_edicao ? 'Editar Alerta' : 'Criar Novo Alerta'; ?></h2>
            <form method="POST" action="gerir_alertas.php" class="alert-form">
                <!-- Campos ocultos -->
                <input type="hidden" name="acao" value="<?php echo $alerta_edicao ? 'editar' : 'criar'; ?>">
                <?php if ($alerta_edicao): ?>
                    <input type="hidden" name="id_alerta" value="<?php echo htmlspecialchars($alerta_edicao['id_alerta']); ?>">
                <?php endif; ?>

                <!-- Informações básicas -->
                <div class="form-group">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" class="form-input"
                           value="<?php echo $alerta_edicao ? htmlspecialchars($alerta_edicao['titulo']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="conteudo">Conteúdo</label>
                    <textarea id="conteudo" name="conteudo" class="form-input" rows="4" required><?php echo $alerta_edicao ? htmlspecialchars($alerta_edicao['conteudo']) : ''; ?></textarea>
                </div>

                <!-- Período de validade -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data de Início</label>
                        <input type="datetime-local" id="data_inicio" name="data_inicio" class="form-input"
                               value="<?php echo $alerta_edicao ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($alerta_edicao['data_inicio']))) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="data_fim">Data de Fim</label>
                        <input type="datetime-local" id="data_fim" name="data_fim" class="form-input"
                               value="<?php echo $alerta_edicao ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($alerta_edicao['data_fim']))) : ''; ?>"
                               required>
                    </div>
                </div>

                <!-- Estado do alerta -->
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="ativo" name="ativo"
                           <?php echo (!$alerta_edicao || $alerta_edicao['ativo']) ? 'checked' : ''; ?>>
                    <label for="ativo">Ativo</label>
                </div>

                <!-- Botões de ação -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <?php echo $alerta_edicao ? 'Atualizar' : 'Criar'; ?>
                    </button>
                    <?php if ($alerta_edicao): ?>
                        <a href="gerir_alertas.php" class="btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Lista de alertas -->
        <section class="list-section">
            <h2>Alertas Existentes</h2>

            <?php if ($result_alertas && $result_alertas->num_rows > 0): ?>
                <div class="alerts-list">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Data Início</th>
                                <th>Data Fim</th>
                                <th>Estado</th>
                                <th>Criado por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($alerta = $result_alertas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alerta['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($alerta['data_inicio']))); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($alerta['data_fim']))); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $alerta['ativo'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $alerta['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($alerta['nome_admin']); ?></td>
                                    <td class="actions">
                                        <!-- Botão de edição -->
                                        <a href="gerir_alertas.php?editar=<?php echo htmlspecialchars($alerta['id_alerta']); ?>"
                                           class="btn-action edit">Editar</a>

                                        <!-- Formulário de eliminação -->
                                        <form method="POST" action="gerir_alertas.php" class="delete-form"
                                              onsubmit="return confirm('Tem a certeza que deseja eliminar este alerta?');">
                                            <input type="hidden" name="id_alerta" value="<?php echo htmlspecialchars($alerta['id_alerta']); ?>">
                                            <button type="submit" name="excluir" class="btn-action delete">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">Não existem alertas registados.</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- Rodapé -->
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

    <!-- Script para validação de datas -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dataInicioInput = document.getElementById('data_inicio');
        const dataFimInput = document.getElementById('data_fim');

        // Função para formatar data e hora no formato aceito pelo input datetime-local
        function formatDateTime(date) {
            return date.toISOString().slice(0, 16);
        }

        // Define data atual como valor mínimo para data de início
        const agora = new Date();
        const agoraFormatado = formatDateTime(agora);

        // Se não houver valor definido, define a data atual como padrão
        if (!dataInicioInput.value) {
            dataInicioInput.value = agoraFormatado;
        }

        // Impede seleção de datas passadas
        dataInicioInput.setAttribute('min', agoraFormatado);

        // Garante que a data de fim seja posterior à data de início
        dataInicioInput.addEventListener('change', function() {
            if (dataFimInput.value && dataFimInput.value < dataInicioInput.value) {
                dataFimInput.value = dataInicioInput.value;
            }
            dataFimInput.setAttribute('min', dataInicioInput.value);
        });
    });
    </script>
</body>
</html>

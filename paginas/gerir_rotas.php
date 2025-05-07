<?php
/**
 * Gestão de Rotas - FelixBus
 *
 * Este ficheiro permite aos administradores gerir rotas e horários de autocarros.
 *
 * Funcionalidades:
 * - Listar todas as rotas existentes
 * - Criar novas rotas com horários
 * - Editar rotas e horários existentes
 * - Eliminar rotas (apenas se não tiverem horários associados)
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
 * Processa as ações do formulário
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // Recolhe e limpa os dados comuns do formulário
    $origem = trim($_POST['origem'] ?? '');
    $destino = trim($_POST['destino'] ?? '');
    $hora_partida = trim($_POST['hora_partida'] ?? '');
    $hora_chegada = trim($_POST['hora_chegada'] ?? '');
    $preco = floatval($_POST['preco'] ?? 0);
    $capacidade = intval($_POST['capacidade'] ?? 0);
    $data_inicio = trim($_POST['data_inicio'] ?? '');
    $data_fim = !empty($_POST['data_fim']) ? trim($_POST['data_fim']) : null;

    // Verifica se os campos obrigatórios estão preenchidos
    $campos_vazios = empty($origem) || empty($destino) || empty($hora_partida) ||
                    empty($hora_chegada) || empty($preco) || empty($capacidade) ||
                    empty($data_inicio);

    switch ($acao) {
        case 'criar':
            if ($campos_vazios) {
                $erro = "Por favor, preencha todos os campos obrigatórios.";
                break;
            }

            $id_admin = $_SESSION['id_utilizador'];

            // Inicia uma transação para garantir a integridade dos dados
            mysqli_begin_transaction($conn);

            try {
                // Insere a rota
                $sql_rota = "INSERT INTO rotas (origem, destino, criado_por) VALUES (?, ?, ?)";
                $stmt_rota = mysqli_prepare($conn, $sql_rota);

                if (!$stmt_rota) {
                    throw new Exception("Erro ao preparar a consulta da rota: " . mysqli_error($conn));
                }

                mysqli_stmt_bind_param($stmt_rota, "ssi", $origem, $destino, $id_admin);

                if (!mysqli_stmt_execute($stmt_rota)) {
                    throw new Exception("Erro ao executar a consulta da rota: " . mysqli_stmt_error($stmt_rota));
                }

                $id_rota = mysqli_insert_id($conn);

                // Prepara a consulta para inserir o horário
                $sql_horario = "INSERT INTO horarios
                               (id_rota, hora_partida, hora_chegada, capacidade_autocarro,
                                lugares_disponiveis, preco, data_inicio" .
                               ($data_fim !== null ? ", data_fim" : "") . ")
                               VALUES (?, ?, ?, ?, ?, ?, ?" .
                               ($data_fim !== null ? ", ?" : "") . ")";

                $stmt_horario = mysqli_prepare($conn, $sql_horario);

                if (!$stmt_horario) {
                    throw new Exception("Erro ao preparar a consulta do horário: " . mysqli_error($conn));
                }

                // Vincula os parâmetros com base na presença ou ausência da data de fim
                if ($data_fim === null) {
                    mysqli_stmt_bind_param($stmt_horario, "issiids",
                        $id_rota, $hora_partida, $hora_chegada,
                        $capacidade, $capacidade, $preco, $data_inicio);
                } else {
                    mysqli_stmt_bind_param($stmt_horario, "issiidss",
                        $id_rota, $hora_partida, $hora_chegada,
                        $capacidade, $capacidade, $preco, $data_inicio, $data_fim);
                }

                if (!mysqli_stmt_execute($stmt_horario)) {
                    throw new Exception("Erro ao executar a consulta do horário: " . mysqli_stmt_error($stmt_horario));
                }

                // Confirma a transação
                mysqli_commit($conn);
                $mensagem = "Rota criada com sucesso!";
                header("Location: gerir_rotas.php?success=1");
                exit();
            } catch (Exception $e) {
                // Reverte a transação em caso de erro
                mysqli_rollback($conn);
                $erro = "Erro ao criar rota: " . $e->getMessage();
            }
            break;

        case 'editar':
            // Verifica se os IDs necessários estão presentes
            if (!isset($_POST['id_rota']) || !isset($_POST['id_horario'])) {
                $erro = "Dados de edição inválidos.";
                break;
            }

            if ($campos_vazios) {
                $erro = "Por favor, preencha todos os campos obrigatórios.";
                break;
            }

            $id_rota = intval($_POST['id_rota']);
            $id_horario = intval($_POST['id_horario']);

            // Inicia uma transação para garantir a integridade dos dados
            mysqli_begin_transaction($conn);

            try {
                // Atualiza a rota
                $sql_rota = "UPDATE rotas SET origem = ?, destino = ? WHERE id_rota = ?";
                $stmt_rota = mysqli_prepare($conn, $sql_rota);
                mysqli_stmt_bind_param($stmt_rota, "ssi", $origem, $destino, $id_rota);
                mysqli_stmt_execute($stmt_rota);

                // Prepara a consulta para atualizar o horário
                if ($data_fim === null) {
                    $sql_horario = "UPDATE horarios SET
                                   hora_partida = ?,
                                   hora_chegada = ?,
                                   capacidade_autocarro = ?,
                                   lugares_disponiveis = ?,
                                   preco = ?,
                                   data_inicio = ?,
                                   data_fim = NULL
                                   WHERE id_horario = ?";

                    $stmt_horario = mysqli_prepare($conn, $sql_horario);
                    mysqli_stmt_bind_param($stmt_horario, "ssiidsi",
                        $hora_partida, $hora_chegada, $capacidade, $capacidade,
                        $preco, $data_inicio, $id_horario);
                } else {
                    $sql_horario = "UPDATE horarios SET
                                   hora_partida = ?,
                                   hora_chegada = ?,
                                   capacidade_autocarro = ?,
                                   lugares_disponiveis = ?,
                                   preco = ?,
                                   data_inicio = ?,
                                   data_fim = ?
                                   WHERE id_horario = ?";

                    $stmt_horario = mysqli_prepare($conn, $sql_horario);
                    mysqli_stmt_bind_param($stmt_horario, "ssiidssi",
                        $hora_partida, $hora_chegada, $capacidade, $capacidade,
                        $preco, $data_inicio, $data_fim, $id_horario);
                }

                mysqli_stmt_execute($stmt_horario);

                // Confirma a transação
                mysqli_commit($conn);
                $mensagem = "Rota e horários atualizados com sucesso!";
                header("Location: gerir_rotas.php?success=1");
                exit();
            } catch (Exception $e) {
                // Reverte a transação em caso de erro
                mysqli_rollback($conn);
                $erro = "Erro ao atualizar rota e horários: " . $e->getMessage();
            }
            break;

        case 'excluir':
            $id_rota = $_POST['id_rota'] ?? 0;

            // Verifica se existem horários associados
            $sql_check = "SELECT COUNT(*) FROM horarios WHERE id_rota = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "i", $id_rota);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_bind_result($stmt_check, $count);
            mysqli_stmt_fetch($stmt_check);
            mysqli_stmt_close($stmt_check);

            if ($count > 0) {
                $erro = "Não é possível eliminar esta rota pois existem horários associados.";
            } else {
                $sql = "DELETE FROM rotas WHERE id_rota = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $id_rota);

                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Rota eliminada com sucesso!";
                } else {
                    $erro = "Erro ao eliminar rota: " . mysqli_error($conn);
                }
            }
            break;
    }
}

/**
 * Obtém dados da rota para edição
 */
$rota_edicao = null;
$horarios_edicao = [];

if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_rota = $_GET['editar'];

    try {
        // Obtém os dados básicos da rota
        $sql = "SELECT r.* FROM rotas r WHERE r.id_rota = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_rota);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $rota_edicao = $row;

            // Obtém os horários associados à rota
            $sql_horarios = "SELECT * FROM horarios WHERE id_rota = ?";
            $stmt_horarios = mysqli_prepare($conn, $sql_horarios);
            mysqli_stmt_bind_param($stmt_horarios, "i", $id_rota);
            mysqli_stmt_execute($stmt_horarios);
            $result_horarios = mysqli_stmt_get_result($stmt_horarios);

            // Armazena os horários e adiciona o primeiro à rota para exibição no formulário
            while ($horario = mysqli_fetch_assoc($result_horarios)) {
                $horarios_edicao[] = $horario;

                // Usa apenas o primeiro horário para preencher o formulário
                if (!isset($rota_edicao['id_horario'])) {
                    $rota_edicao['id_horario'] = $horario['id_horario'];
                    $rota_edicao['hora_partida'] = $horario['hora_partida'];
                    $rota_edicao['hora_chegada'] = $horario['hora_chegada'];
                    $rota_edicao['capacidade_autocarro'] = $horario['capacidade_autocarro'];
                    $rota_edicao['preco'] = $horario['preco'];
                    $rota_edicao['data_inicio'] = $horario['data_inicio'] ?? '';
                    $rota_edicao['data_fim'] = $horario['data_fim'] ?? '';
                }
            }
        }
    } catch (Exception $e) {
        $erro = "Erro ao carregar dados da rota: " . $e->getMessage();
    }
}

/**
 * Obtém todas as rotas para exibição na tabela
 */
try {
    $sql = "SELECT
            r.id_rota, r.origem, r.destino,
            u.nome_completo as nome_admin,
            'Clique em Editar para ver detalhes' as horarios
            FROM rotas r
            JOIN utilizadores u ON r.criado_por = u.id_utilizador
            ORDER BY r.origem ASC";

    $result_rotas = mysqli_query($conn, $sql);

    if (!$result_rotas) {
        throw new Exception(mysqli_error($conn));
    }
} catch (Exception $e) {
    $erro = "Erro ao carregar lista de rotas: " . $e->getMessage();
    $result_rotas = false;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Rotas - FelixBus</title>
    <link rel="stylesheet" href="gerir_rotas.css">
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
        <h1>Gestão de Rotas</h1>

        <!-- Mensagens de alerta -->
        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- Formulário de Criação/Edição -->
        <section class="form-section">
            <h2><?php echo $rota_edicao ? 'Editar Rota' : 'Criar Nova Rota'; ?></h2>
            <form method="POST" class="form-section">
                <div class="form-grid">
                    <!-- Campos ocultos -->
                    <input type="hidden" name="acao" value="<?php echo isset($rota_edicao) ? 'editar' : 'criar'; ?>">
                    <?php if (isset($rota_edicao)): ?>
                        <input type="hidden" name="id_rota" value="<?php echo $rota_edicao['id_rota']; ?>">
                        <input type="hidden" name="id_horario" value="<?php echo $rota_edicao['id_horario']; ?>">
                    <?php endif; ?>

                    <!-- Dados da rota -->
                    <div class="form-group">
                        <label class="required">Origem</label>
                        <input type="text" name="origem" required
                               value="<?php echo isset($rota_edicao) ? $rota_edicao['origem'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Destino</label>
                        <input type="text" name="destino" required
                               value="<?php echo isset($rota_edicao) ? $rota_edicao['destino'] : ''; ?>">
                    </div>

                    <!-- Horários -->
                    <div class="form-group">
                        <label class="required">Hora de Partida</label>
                        <input type="time" name="hora_partida" required
                               value="<?php echo isset($rota_edicao) ? $rota_edicao['hora_partida'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Hora de Chegada</label>
                        <input type="time" name="hora_chegada" required
                               value="<?php echo isset($rota_edicao) ? $rota_edicao['hora_chegada'] : ''; ?>">
                    </div>

                    <!-- Detalhes do autocarro -->
                    <div class="form-group">
                        <label class="required">Preço (€)</label>
                        <input type="number" name="preco" step="0.01" min="0" required
                               value="<?php echo isset($rota_edicao) ? $rota_edicao['preco'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Capacidade</label>
                        <input type="number" name="capacidade" min="1" required
                               value="<?php echo isset($rota_edicao) ? $rota_edicao['capacidade_autocarro'] : ''; ?>">
                    </div>

                    <!-- Datas de validade -->
                    <div class="date-range">
                        <div class="form-group">
                            <label class="required">Data de Início</label>
                            <input type="date" name="data_inicio" required
                                   value="<?php echo isset($rota_edicao) ? $rota_edicao['data_inicio'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Data de Fim</label>
                            <input type="date" name="data_fim"
                                   value="<?php echo isset($rota_edicao) ? $rota_edicao['data_fim'] : ''; ?>">
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-submit">
                            <?php echo isset($rota_edicao) ? 'Atualizar' : 'Criar'; ?>
                        </button>
                        <a href="gerir_rotas.php" class="btn btn-cancel">Cancelar</a>
                    </div>
                </div>
            </form>
        </section>

        <!-- Lista de Rotas -->
        <section class="lista-rotas">
            <h2>Rotas Existentes</h2>
            <?php if ($result_rotas && mysqli_num_rows($result_rotas) > 0): ?>
                <div class="table-responsive">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Horários</th>
                                <th>Criado por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rota = mysqli_fetch_assoc($result_rotas)): ?>
                                <tr>
                                    <td><?php echo $rota['origem']; ?></td>
                                    <td><?php echo $rota['destino']; ?></td>
                                    <td><?php echo nl2br($rota['horarios']); ?></td>
                                    <td><?php echo $rota['nome_admin']; ?></td>
                                    <td>
                                        <a href="?editar=<?php echo $rota['id_rota']; ?>" class="btn-action">Editar</a>
                                        <a href="?excluir=<?php echo $rota['id_rota']; ?>"
                                           class="btn-action btn-delete"
                                           onclick="return confirm('Tem a certeza que deseja eliminar esta rota?')">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-results">Não existem rotas registadas.</p>
            <?php endif; ?>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="footer">
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>

    <!-- Script para manipulação de datas no formulário -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Formata a data no formato YYYY-MM-DD
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        // Obtém o input de data de início
        const dataInicioInput = document.querySelector('input[name="data_inicio"]');
        const dataFimInput = document.querySelector('input[name="data_fim"]');

        // Se não houver valor definido, define a data atual como padrão
        if (!dataInicioInput.value) {
            const hoje = new Date();
            dataInicioInput.value = formatDate(hoje);
        }

        // Impede seleção de datas passadas
        const hoje = new Date();
        dataInicioInput.setAttribute('min', formatDate(hoje));

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

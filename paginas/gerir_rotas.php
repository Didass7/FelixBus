<?php
/**
 * Gestão de Rotas - FelixBus
 *
 * Este script permite aos administradores gerir rotas e horários de autocarros.
 * Funcionalidades:
 * - Listar todas as rotas existentes
 * - Criar novas rotas com horários
 * - Editar rotas e horários existentes
 * - Excluir rotas (apenas se não tiverem horários associados)
 */

// Iniciar a sessão para aceder aos dados do utilizador autenticado
session_start();

// Incluir o ficheiro de conexão à base de dados
include '../basedados/basedados.h';

// Verificar se o utilizador está autenticado e tem perfil de administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

// Selecionar a base de dados
mysqli_select_db($conn, 'felixbus') or die("Não foi possível selecionar o banco de dados");

$mensagem = '';
$erro = '';

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'criar':
                $origem = trim($_POST['origem']);
                $destino = trim($_POST['destino']);
                $hora_partida = trim($_POST['hora_partida']);
                $hora_chegada = trim($_POST['hora_chegada']);
                $preco = floatval($_POST['preco']);
                $capacidade = intval($_POST['capacidade']);
                $data_inicio = trim($_POST['data_inicio']);
                $data_fim = !empty($_POST['data_fim']) ? trim($_POST['data_fim']) : null;
                $id_admin = $_SESSION['id_utilizador'];

                if (empty($origem) || empty($destino) || empty($hora_partida) ||
                    empty($hora_chegada) || empty($preco) || empty($capacidade) ||
                    empty($data_inicio)) {
                    $erro = "Preencha todos os campos obrigatórios.";
                } else {
                    mysqli_begin_transaction($conn);
                    try {
                        // Debug: Imprimir valores antes da inserção
                        error_log("Dados a inserir: " . print_r([
                            'origem' => $origem,
                            'destino' => $destino,
                            'hora_partida' => $hora_partida,
                            'hora_chegada' => $hora_chegada,
                            'preco' => $preco,
                            'capacidade' => $capacidade,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim
                        ], true));

                        // Inserir rota
                        $sql_rota = "INSERT INTO rotas (origem, destino, criado_por) VALUES (?, ?, ?)";
                        $stmt_rota = mysqli_prepare($conn, $sql_rota);
                        if (!$stmt_rota) {
                            throw new Exception("Erro ao preparar query da rota: " . mysqli_error($conn));
                        }
                        
                        mysqli_stmt_bind_param($stmt_rota, "ssi", $origem, $destino, $id_admin);
                        if (!mysqli_stmt_execute($stmt_rota)) {
                            throw new Exception("Erro ao executar query da rota: " . mysqli_stmt_error($stmt_rota));
                        }
                        
                        $id_rota = mysqli_insert_id($conn);

                        // Inserir horário
                        $sql_horario = "INSERT INTO horarios 
                                       (id_rota, hora_partida, hora_chegada, capacidade_autocarro, 
                                        lugares_disponiveis, preco, data_inicio" . 
                                       ($data_fim !== null ? ", data_fim" : "") . ") 
                                       VALUES 
                                       (?, ?, ?, ?, ?, ?, ?" .
                                       ($data_fim !== null ? ", ?" : "") . ")";

                        error_log("SQL Horário: " . $sql_horario); // Debug

                        $stmt_horario = mysqli_prepare($conn, $sql_horario);
                        if (!$stmt_horario) {
                            throw new Exception("Erro ao preparar query do horário: " . mysqli_error($conn));
                        }

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
                            throw new Exception("Erro ao executar query do horário: " . mysqli_stmt_error($stmt_horario));
                        }

                        mysqli_commit($conn);
                        $mensagem = "Rota criada com sucesso!";
                        header("Location: gerir_rotas.php?success=1");
                        exit();
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        error_log("Erro na criação de rota/horário: " . $e->getMessage());
                        $erro = "Erro ao criar rota: " . $e->getMessage();
                    }
                }
                break;

            case 'editar':
                // Verificar se todos os campos necessários existem
                if (!isset($_POST['id_rota']) || !isset($_POST['id_horario'])) {
                    $erro = "Dados de edição inválidos.";
                    break;
                }

                $id_rota = intval($_POST['id_rota']);
                $id_horario = intval($_POST['id_horario']);
                $origem = trim($_POST['origem']);
                $destino = trim($_POST['destino']);
                $hora_partida = trim($_POST['hora_partida']);
                $hora_chegada = trim($_POST['hora_chegada']);
                $preco = floatval($_POST['preco']);
                $capacidade = intval($_POST['capacidade']);
                $data_inicio = trim($_POST['data_inicio']);
                $data_fim = !empty($_POST['data_fim']) ? trim($_POST['data_fim']) : null;

                if (empty($origem) || empty($destino) || empty($hora_partida) ||
                    empty($hora_chegada) || empty($preco) || empty($capacidade) ||
                    empty($data_inicio)) {
                    $erro = "Preencha todos os campos obrigatórios.";
                } else {
                    mysqli_begin_transaction($conn);
                    try {
                        // Atualizar rota
                        $sql_rota = "UPDATE rotas SET origem = ?, destino = ? WHERE id_rota = ?";
                        $stmt_rota = mysqli_prepare($conn, $sql_rota);
                        mysqli_stmt_bind_param($stmt_rota, "ssi", $origem, $destino, $id_rota);
                        mysqli_stmt_execute($stmt_rota);

                        // Atualizar horário
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
                                $hora_partida,
                                $hora_chegada,
                                $capacidade,
                                $capacidade,
                                $preco,
                                $data_inicio,
                                $id_horario
                            );
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
                                $hora_partida,
                                $hora_chegada,
                                $capacidade,
                                $capacidade,
                                $preco,
                                $data_inicio,
                                $data_fim,
                                $id_horario
                            );
                        }

                        mysqli_stmt_execute($stmt_horario);

                        mysqli_commit($conn);
                        $mensagem = "Rota e horários atualizados com sucesso!";
                        header("Location: gerir_rotas.php?success=1");
                        exit();
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $erro = "Erro ao atualizar rota e horários: " . $e->getMessage();
                    }
                }
                break;

            case 'excluir':
                $id_rota = $_POST['id_rota'];

                // Verificar se existem horários associados
                $sql_check = "SELECT COUNT(*) FROM horarios WHERE id_rota = ?";
                $stmt_check = mysqli_prepare($conn, $sql_check);
                mysqli_stmt_bind_param($stmt_check, "i", $id_rota);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_bind_result($stmt_check, $count);
                mysqli_stmt_fetch($stmt_check);
                mysqli_stmt_close($stmt_check);

                if ($count > 0) {
                    $erro = "Não é possível excluir esta rota pois existem horários associados.";
                } else {
                    $sql = "DELETE FROM rotas WHERE id_rota = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $id_rota);

                    if (mysqli_stmt_execute($stmt)) {
                        $mensagem = "Rota excluída com sucesso!";
                    } else {
                        $erro = "Erro ao excluir rota: " . mysqli_error($conn);
                    }
                }
                break;
        }
    }
}

// Buscar rota para edição
$rota_edicao = null;
$horarios_edicao = [];
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_rota = $_GET['editar'];

    // Buscar dados da rota
    // NOTA: Esta consulta foi modificada para evitar problemas com a estrutura da tabela horários
    // Primeiro, buscamos apenas os dados da rota sem juntar com a tabela horários
    $sql = "SELECT r.* FROM rotas r WHERE r.id_rota = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_rota);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $rota_edicao = $row;

        // Agora tentamos buscar os horários separadamente, com tratamento de erro
        try {
            $sql_horarios = "SELECT * FROM horarios WHERE id_rota = ?";
            $stmt_horarios = mysqli_prepare($conn, $sql_horarios);
            mysqli_stmt_bind_param($stmt_horarios, "i", $id_rota);
            mysqli_stmt_execute($stmt_horarios);
            $result_horarios = mysqli_stmt_get_result($stmt_horarios);

            while ($horario = mysqli_fetch_assoc($result_horarios)) {
                $horarios_edicao[] = $horario;
                // Adicionar os dados do horário à rota para exibição no formulário
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
        } catch (Exception $e) {
            // Se houver erro ao buscar horários, apenas continuamos com os dados da rota
            // e deixamos os campos de horário vazios
        }
    }
}

// Buscar todas as rotas para exibição na tabela
// NOTA: Esta consulta foi simplificada para evitar problemas com a estrutura da tabela horários
// A consulta original estava a causar um erro: "Unknown column 'h.data_inicio' in 'field list'"
// Isto pode acontecer se a tabela horários não tiver a coluna data_inicio ou se a tabela não existir
// Para resolver o problema, removemos a junção com a tabela horários e simplificámos a consulta
$sql = "SELECT
        r.id_rota, r.origem, r.destino,
        u.nome_completo as nome_admin,
        'Clique em Editar para ver detalhes' as horarios
        FROM rotas r
        JOIN utilizadores u ON r.criado_por = u.id_utilizador
        ORDER BY r.origem ASC";

// Executar a consulta SQL
$result_rotas = mysqli_query($conn, $sql);

// Verificar se a consulta foi executada com sucesso
// Em caso de erro, interromper a execução e mostrar a mensagem de erro
if (!$result_rotas) {
    die("Erro na consulta: " . mysqli_error($conn));
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
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_admin.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="pagina_inicial_admin.php" class="nav-link">Painel de Administração</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Gestão de Rotas</h1>

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
                    <input type="hidden" name="acao" value="<?php echo isset($rota_edicao) ? 'editar' : 'criar'; ?>">
                    <?php if (isset($rota_edicao)): ?>
                        <input type="hidden" name="id_rota" value="<?php echo $rota_edicao['id_rota']; ?>">
                        <input type="hidden" name="id_horario" value="<?php echo $rota_edicao['id_horario']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="required">Origem</label>
                        <input type="text" name="origem" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['origem']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Destino</label>
                        <input type="text" name="destino" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['destino']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Hora de Partida</label>
                        <input type="time" name="hora_partida" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['hora_partida']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Hora de Chegada</label>
                        <input type="time" name="hora_chegada" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['hora_chegada']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Preço</label>
                        <input type="number" name="preco" step="0.01" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['preco']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Capacidade</label>
                        <input type="number" name="capacidade" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['capacidade_autocarro']) : ''; ?>">
                    </div>

                    <div class="date-range">
                        <div class="form-group">
                            <label class="required">Data de Início</label>
                            <input type="date" name="data_inicio" required
                                   value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['data_inicio']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Data de Fim</label>
                            <input type="date" name="data_fim"
                                   value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['data_fim']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-submit">
                            <?php echo isset($rota_edicao) ? 'Atualizar Rota' : 'Criar Rota'; ?>
                        </button>
                        <a href="gerir_rotas.php" class="btn btn-cancel">Cancelar</a>
                    </div>
                </div>
            </form>
        </section>

        <!-- Lista de Rotas -->
        <section class="lista-rotas">
            <h2>Rotas Existentes</h2>
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
                                <td><?php echo htmlspecialchars($rota['origem']); ?></td>
                                <td><?php echo htmlspecialchars($rota['destino']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($rota['horarios'])); ?></td>
                                <td><?php echo htmlspecialchars($rota['nome_admin']); ?></td>
                                <td>
                                    <a href="?editar=<?php echo $rota['id_rota']; ?>" class="btn-action">Editar</a>
                                    <a href="?excluir=<?php echo $rota['id_rota']; ?>"
                                       class="btn-action btn-delete"
                                       onclick="return confirm('Tem certeza que deseja excluir esta rota?')">Excluir</a>
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

    <!-- Script para manipulação de datas no formulário -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para formatar a data no formato YYYY-MM-DD
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        // Pegar o input de data de início
        const dataInicioInput = document.querySelector('input[name="data_inicio"]');

        // Sempre definir a data atual como padrão
        const hoje = new Date();
        dataInicioInput.value = formatDate(hoje);

        // Impedir seleção de datas passadas
        dataInicioInput.setAttribute('min', formatDate(hoje));
    });
    </script>
</body>
</html>

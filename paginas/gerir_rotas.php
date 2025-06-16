<?php
// inicia sessão e inclui ligação à base de dados
session_start();
include '../basedados/basedados.h';

$mensagem = '';
$erro = '';

// valida autenticação de administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

// processamento de formulário: ações criar, editar e excluir rotas e horários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    // recolhe dados do formulário e valida campos obrigatórios
    $acao = $_POST['acao'];

    $origem = trim($_POST['origem'] ?? '');
    $destino = trim($_POST['destino'] ?? '');
    $hora_partida = trim($_POST['hora_partida'] ?? '');
    $hora_chegada = trim($_POST['hora_chegada'] ?? '');
    $preco = (double)($_POST['preco'] ?? 0);
    $capacidade = intval($_POST['capacidade'] ?? 0);
    $data_inicio = trim($_POST['data_inicio'] ?? '');
    $data_fim = !empty($_POST['data_fim']) ? trim($_POST['data_fim']) : null;

    $campos_vazios = empty($origem) || empty($destino) || empty($hora_partida) ||
                    empty($hora_chegada) || empty($preco) || empty($capacidade) ||
                    empty($data_inicio);

    switch ($acao) {
        // criar nova rota e horário
        case 'criar':
            if ($campos_vazios) {
                $erro = "Por favor, preencha todos os campos obrigatórios.";
                break;
            }

            $id_admin = $_SESSION['id_utilizador'];

            try {
                // Insere a rota
                $sql_rota = "INSERT INTO rotas (origem, destino, criado_por) VALUES (?, ?, ?)";
                $stmt_rota = $conn->prepare($sql_rota);

                if (!$stmt_rota) {
                    throw new Exception("Erro ao preparar a consulta da rota: " . mysqli_error($conn));
                }

                $stmt_rota->bind_param("ssi", $origem, $destino, $id_admin);

                if (!$stmt_rota->execute()) {
                    throw new Exception("Erro ao executar a consulta da rota: " . htmlspecialchars($stmt_rota->error));
                }

                $id_rota = $stmt_rota->insert_id;

                // Prepara a consulta para inserir o horário
                $sql_horario = "INSERT INTO horarios
                               (id_rota, hora_partida, hora_chegada, capacidade_autocarro,
                                lugares_disponiveis, preco, data_inicio" .
                               ($data_fim !== null ? ", data_fim" : "") . ")
                               VALUES (?, ?, ?, ?, ?, ?, ?" .
                               ($data_fim !== null ? ", ?" : "") . ")";

                $stmt_horario = $conn->prepare($sql_horario);

                if (!$stmt_horario) {
                    throw new Exception("Erro ao preparar a consulta do horário: " . (is_object($conn) ? htmlspecialchars($conn->error) : "Conexão não disponível"));
                }

                // Vincula os parâmetros com base na presença ou ausência da data de fim
                if ($data_fim === null) {
                    $stmt_horario->bind_param("issiids",
                        $id_rota, $hora_partida, $hora_chegada,
                        $capacidade, $capacidade, $preco, $data_inicio);
                } else {
                    $stmt_horario->bind_param("ssiidss",
                        $id_rota, $hora_partida, $hora_chegada,
                        $capacidade, $capacidade, $preco, $data_inicio, $data_fim);
                }

                if (!$stmt_horario->execute()) {
                    throw new Exception("Erro ao executar a consulta do horário: " . htmlspecialchars($stmt_horario->error));
                }

                $mensagem = "Rota criada com sucesso!";
                header("Location: gerir_rotas.php?success=1");
                exit();
            } catch (Exception $e) {
                $erro = "Erro ao criar rota: " . htmlspecialchars($e->getMessage());
            }
            break;

        // editar rota e horário existente
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

            try {
                // Atualiza a rota
                $sql_rota = "UPDATE rotas SET origem = ?, destino = ? WHERE id_rota = ?";
                $stmt_rota = $conn->prepare($sql_rota);

                if (!$stmt_rota) {
                    throw new Exception("Erro ao preparar a consulta da rota: " . htmlspecialchars($stmt_rota->error));
                }

                $stmt_rota->bind_param("ssi", $origem, $destino, $id_rota);

                if (!$stmt_rota->execute()) {
                    throw new Exception("Erro ao executar a consulta da rota: " . htmlspecialchars($stmt_rota->error));
                }

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

                    $stmt_horario = $conn->prepare($sql_horario);

                    if (!$stmt_horario) {
                        throw new Exception("Erro ao preparar a consulta do horário: " . htmlspecialchars($stmt_rota->error));
                    }

                    $stmt_horario->bind_param("ssiidsi",
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

                    $stmt_horario = $conn->prepare($sql_horario);

                    if (!$stmt_horario) {
                        throw new Exception("Erro ao preparar a consulta do horário: " . htmlspecialchars($stmt_rota->error));
                    }

                    $stmt_horario->bind_param("ssiidssi",
                        $hora_partida, $hora_chegada, $capacidade, $capacidade,
                        $preco, $data_inicio, $data_fim, $id_horario);
                }

                if (!$stmt_horario->execute()) {
                    throw new Exception("Erro ao executar a consulta do horário: " . htmlspecialchars($stmt_horario->error));
                }

                $mensagem = "Rota e horários atualizados com sucesso!";
                header("Location: gerir_rotas.php?success=1");
                exit();
            } catch (Exception $e) {
                $erro = "Erro ao atualizar rota e horários: " . htmlspecialchars($e->getMessage());
            }
            break;

        // excluir rota sem horários associados
        case 'excluir':
            $id_rota = $_POST['id_rota'] ?? 0;

            try {
                // Verifica se existem horários associados
                $sql_check = "SELECT COUNT(*) FROM horarios WHERE id_rota = ?";
                $stmt_check = $conn->prepare($sql_check);

                if (!$stmt_check) {
                    throw new Exception("Erro ao preparar a consulta de verificação: " . htmlspecialchars($stmt_check->error));
                }

                $stmt_check->bind_param("i", $id_rota);

                if (!$stmt_check->execute()) {
                    throw new Exception("Erro ao executar a consulta de verificação: " . htmlspecialchars($stmt_check->error));
                }

                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $erro = "Não é possível eliminar esta rota pois existem horários associados.";
                } else {
                    $sql = "DELETE FROM rotas WHERE id_rota = ?";
                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        throw new Exception("Erro ao preparar a consulta de exclusão: " . htmlspecialchars($stmt->error));
                    }

                    $stmt->bind_param("i", $id_rota);

                    if (!$stmt->execute()) {
                        throw new Exception("Erro ao executar a consulta de exclusão: " . htmlspecialchars($stmt->error));
                    }

                    $mensagem = "Rota eliminada com sucesso!";
                }
            } catch (Exception $e) {
                $erro = "Erro ao processar exclusão: " . htmlspecialchars($e->getMessage());
            }
            break;
    }
}

// carregamento de dados para edição de rota e horários
$rota_edicao = null;
$horarios_edicao = [];

if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_rota = $_GET['editar'];

    try {
        // Obtém os dados básicos da rota
        $sql = "SELECT r.* FROM rotas r WHERE r.id_rota = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro ao preparar a consulta da rota: " . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("i", $id_rota);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar a consulta da rota: " . htmlspecialchars($stmt->error));
        }

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $rota_edicao = $row;

            // Obtém os horários associados à rota
            $sql_horarios = "SELECT * FROM horarios WHERE id_rota = ?";
            $stmt_horarios = $conn->prepare($sql_horarios);

            if (!$stmt_horarios) {
                throw new Exception("Erro ao preparar a consulta de horários: " . htmlspecialchars($conn->error));
            }

            $stmt_horarios->bind_param("i", $id_rota);

            if (!$stmt_horarios->execute()) {
                throw new Exception("Erro ao executar a consulta de horários: " . htmlspecialchars($stmt_horarios->error));
            }

            $result_horarios = $stmt_horarios->get_result();

            // Armazena os horários e adiciona o primeiro à rota para exibição no formulário
            while ($horario = $result_horarios->fetch_assoc()) {
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
        $erro = "Erro ao carregar dados da rota: " . htmlspecialchars($e->getMessage());
    }
}

// obtenção de todas as rotas existentes para exibição
try {
    $sql = "SELECT
            r.id_rota, r.origem, r.destino,
            u.nome_completo as nome_admin,
            'Clique em Editar para ver detalhes' as horarios
            FROM rotas r
            JOIN utilizadores u ON r.criado_por = u.id_utilizador
            ORDER BY r.origem ASC";

    $result_rotas = $conn->query($sql);

    if (!$result_rotas) {
        throw new Exception("Erro ao carregar lista de rotas: " . ($conn ? htmlspecialchars($conn->error) : "Conexão não disponível"));
    }
} catch (Exception $e) {
    $erro = "Erro ao carregar lista de rotas: " . htmlspecialchars($e->getMessage());
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
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <!-- Formulário de Criação/Edição -->
        <section class="form-section">
            <h2><?php echo $rota_edicao ? 'Editar Rota' : 'Criar Nova Rota'; ?></h2>
            <form method="POST" class="form-section">
                <div class="form-grid">
                    <!-- Campos ocultos -->
                    <input type="hidden" name="acao" value="<?php echo isset($rota_edicao) ? 'editar' : 'criar'; ?>">
                    <?php if (isset($rota_edicao)): ?>
                        <input type="hidden" name="id_rota" value="<?php echo htmlspecialchars($rota_edicao['id_rota']); ?>">
                        <input type="hidden" name="id_horario" value="<?php echo htmlspecialchars($rota_edicao['id_horario']); ?>">
                    <?php endif; ?>

                    <!-- Dados da rota -->
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

                    <!-- Horários -->
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

                    <!-- Detalhes do autocarro -->
                    <div class="form-group">
                        <label class="required">Preço (€)</label>
                        <input type="number" name="preco" step="0.01" min="0" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['preco']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="required">Capacidade</label>
                        <input type="number" name="capacidade" min="1" required
                               value="<?php echo isset($rota_edicao) ? htmlspecialchars($rota_edicao['capacidade_autocarro']) : ''; ?>">
                    </div>

                    <!-- Datas de validade -->
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
            <?php if ($result_rotas && $result_rotas->num_rows > 0): ?>
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
                            <?php while ($rota = $result_rotas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rota['origem']); ?></td>
                                    <td><?php echo htmlspecialchars($rota['destino']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($rota['horarios'])); ?></td>
                                    <td><?php echo htmlspecialchars($rota['nome_admin']); ?></td>
                                    <td>
                                        <a href="?editar=<?php echo htmlspecialchars($rota['id_rota']); ?>" class="btn-action">Editar</a>
                                        <a href="?excluir=<?php echo htmlspecialchars($rota['id_rota']); ?>"
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

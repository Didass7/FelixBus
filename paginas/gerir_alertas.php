<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o usuário é administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$erro = '';

// Processar formulário de criação/edição de alerta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        // Obter dados do formulário
        $titulo = trim($_POST['titulo']);
        $conteudo = trim($_POST['conteudo']);
        $data_inicio = $_POST['data_inicio'];
        $data_fim = $_POST['data_fim'];
        $id_admin = $_SESSION['id_utilizador'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Validar dados
        if (empty($titulo) || empty($conteudo) || empty($data_inicio) || empty($data_fim)) {
            $erro = "Todos os campos são obrigatórios.";
        } else {
            // Verificar se as datas são válidas
            $data_inicio_obj = new DateTime($data_inicio);
            $data_fim_obj = new DateTime($data_fim);
            $agora = new DateTime();
            
            if ($data_fim_obj < $data_inicio_obj) {
                $erro = "A data de fim deve ser posterior à data de início.";
            } else {
                // Criar ou atualizar alerta
                if ($_POST['acao'] === 'criar') {
                    $sql = "INSERT INTO alertas (titulo, conteudo, data_inicio, data_fim, criado_por, ativo) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssssii", $titulo, $conteudo, $data_inicio, $data_fim, $id_admin, $ativo);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $mensagem = "Alerta criado com sucesso!";
                    } else {
                        $erro = "Erro ao criar alerta: " . mysqli_error($conn);
                    }
                } elseif ($_POST['acao'] === 'editar' && isset($_POST['id_alerta'])) {
                    $id_alerta = $_POST['id_alerta'];
                    $sql = "UPDATE alertas SET titulo = ?, conteudo = ?, data_inicio = ?, data_fim = ?, ativo = ? 
                            WHERE id_alerta = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ssssii", $titulo, $conteudo, $data_inicio, $data_fim, $ativo, $id_alerta);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $mensagem = "Alerta atualizado com sucesso!";
                    } else {
                        $erro = "Erro ao atualizar alerta: " . mysqli_error($conn);
                    }
                }
            }
        }
    } elseif (isset($_POST['excluir']) && isset($_POST['id_alerta'])) {
        // Excluir alerta
        $id_alerta = $_POST['id_alerta'];
        $sql = "DELETE FROM alertas WHERE id_alerta = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_alerta);
        
        if (mysqli_stmt_execute($stmt)) {
            $mensagem = "Alerta excluído com sucesso!";
        } else {
            $erro = "Erro ao excluir alerta: " . mysqli_error($conn);
        }
    }
}

// Buscar alerta para edição
$alerta_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_alerta = $_GET['editar'];
    $sql = "SELECT * FROM alertas WHERE id_alerta = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_alerta);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $alerta_edicao = $row;
    }
}

// Buscar todos os alertas
$sql_alertas = "SELECT a.*, u.nome_completo as nome_admin 
                FROM alertas a 
                JOIN utilizadores u ON a.criado_por = u.id_utilizador 
                ORDER BY a.data_criacao DESC";
$result_alertas = mysqli_query($conn, $sql_alertas);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Gestão de Alertas</title>
    <link rel="stylesheet" href="gerir_alertas.css">
</head>
<body>
    <!-- Navigation -->
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

    <!-- Main Content -->
    <div class="container">
        <h1 class="page-title">Gestão de Alertas e Promoções</h1>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-error">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>
        
        <!-- Form Section -->
        <section class="form-section">
            <h2><?php echo $alerta_edicao ? 'Editar Alerta' : 'Criar Novo Alerta'; ?></h2>
            <form method="POST" action="gerir_alertas.php" class="alert-form">
                <input type="hidden" name="acao" value="<?php echo $alerta_edicao ? 'editar' : 'criar'; ?>">
                <?php if ($alerta_edicao): ?>
                    <input type="hidden" name="id_alerta" value="<?php echo $alerta_edicao['id_alerta']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" class="form-input" value="<?php echo $alerta_edicao ? htmlspecialchars($alerta_edicao['titulo']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="conteudo">Conteúdo</label>
                    <textarea id="conteudo" name="conteudo" class="form-input" rows="4" required><?php echo $alerta_edicao ? htmlspecialchars($alerta_edicao['conteudo']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data de Início</label>
                        <input type="datetime-local" id="data_inicio" name="data_inicio" class="form-input" value="<?php echo $alerta_edicao ? date('Y-m-d\TH:i', strtotime($alerta_edicao['data_inicio'])) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_fim">Data de Fim</label>
                        <input type="datetime-local" id="data_fim" name="data_fim" class="form-input" value="<?php echo $alerta_edicao ? date('Y-m-d\TH:i', strtotime($alerta_edicao['data_fim'])) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="ativo" name="ativo" <?php echo (!$alerta_edicao || $alerta_edicao['ativo']) ? 'checked' : ''; ?>>
                    <label for="ativo">Ativo</label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><?php echo $alerta_edicao ? 'Atualizar Alerta' : 'Criar Alerta'; ?></button>
                    <?php if ($alerta_edicao): ?>
                        <a href="gerir_alertas.php" class="btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        
        <!-- List Section -->
        <section class="list-section">
            <h2>Alertas Existentes</h2>
            
            <?php if (mysqli_num_rows($result_alertas) > 0): ?>
                <div class="alerts-list">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Data Início</th>
                                <th>Data Fim</th>
                                <th>Status</th>
                                <th>Criado por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($alerta = mysqli_fetch_assoc($result_alertas)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alerta['titulo']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($alerta['data_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($alerta['data_fim'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $alerta['ativo'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $alerta['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($alerta['nome_admin']); ?></td>
                                    <td class="actions">
                                        <a href="gerir_alertas.php?editar=<?php echo $alerta['id_alerta']; ?>" class="btn-action edit">Editar</a>
                                        <form method="POST" action="gerir_alertas.php" class="delete-form" onsubmit="return confirm('Tem certeza que deseja excluir este alerta?');">
                                            <input type="hidden" name="id_alerta" value="<?php echo $alerta['id_alerta']; ?>">
                                            <button type="submit" name="excluir" class="btn-action delete">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">Nenhum alerta cadastrado.</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- Footer -->
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

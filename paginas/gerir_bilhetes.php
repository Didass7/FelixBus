<?php
session_start();
include '../basedados/basedados.h';

// Verificar permissões
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

// Inicializar mensagens
$mensagem = '';
$erro = '';

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpar a mensagem após uso
}

// Função para gerar código de bilhete único
function gerarCodigoBilhete($conn) {
    do {
        $codigo = '';
        $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        $sql = "SELECT 1 FROM bilhetes WHERE codigo_bilhete = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $codigo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
    } while (mysqli_num_rows($resultado) > 0);
    
    return $codigo;
}

// Processar compra de bilhete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar inputs
    if (empty($_POST['id_cliente'])) {
        $erro = "Erro: Por favor, selecione um cliente.";
    } 
    elseif (empty($_POST['id_horario'])) {
        $erro = "Erro: Por favor, selecione um horário.";
    }
    elseif (empty($_POST['data_viagem'])) {
        $erro = "Erro: Por favor, selecione uma data para a viagem.";
    }
    elseif (empty($_POST['quantidade']) || !is_numeric($_POST['quantidade']) || $_POST['quantidade'] < 1) {
        $erro = "Erro: A quantidade de bilhetes deve ser um número maior que zero.";
    }
    elseif ($_POST['quantidade'] > 10) {
        $erro = "Erro: Não é possível comprar mais de 10 bilhetes por vez.";
    }
    else {
        $id_cliente = $_POST['id_cliente'];
        $id_horario = $_POST['id_horario'];
        $data_viagem = $_POST['data_viagem'];
        $quantidade = $_POST['quantidade'];

        // Verificar se a data é válida
        $data_atual = date('Y-m-d');
        $data_limite = date('Y-m-d', strtotime('+30 days'));
        
        if ($data_viagem < $data_atual) {
            $erro = "Erro: Não é possível comprar bilhetes para datas passadas.";
        }
        elseif ($data_viagem > $data_limite) {
            $erro = "Erro: Só é possível comprar bilhetes até 30 dias no futuro.";
        }
        else {
            // Buscar informações do horário
            $sql_horario = "SELECT h.*, r.origem, r.destino 
                           FROM horarios h 
                           INNER JOIN rotas r ON h.id_rota = r.id_rota 
                           WHERE h.id_horario = ?";
            $stmt = mysqli_prepare($conn, $sql_horario);
            mysqli_stmt_bind_param($stmt, "i", $id_horario);
            mysqli_stmt_execute($stmt);
            $horario_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$horario_info) {
                $erro = "Erro: Horário não encontrado.";
            } else {
                // Verificar lugares disponíveis
                if ($horario_info['lugares_disponiveis'] < $quantidade) {
                    $erro = "Erro: Não há lugares suficientes disponíveis. Lugares restantes: " . $horario_info['lugares_disponiveis'];
                } else {
                    mysqli_begin_transaction($conn);
                    try {
                        // Buscar informações da carteira do cliente
                        $sql_carteira = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
                        $stmt = mysqli_prepare($conn, $sql_carteira);
                        mysqli_stmt_bind_param($stmt, "i", $id_cliente);
                        mysqli_stmt_execute($stmt);
                        $carteira_cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                        // Buscar carteira da empresa
                        $sql_empresa = "SELECT id_carteira FROM carteiras WHERE tipo = 'empresa' LIMIT 1";
                        $result_empresa = mysqli_query($conn, $sql_empresa);
                        $carteira_empresa = mysqli_fetch_assoc($result_empresa);

                        // Calcular valor total
                        $valor_total = $horario_info['preco'] * $quantidade;

                        // Verificar se cliente tem saldo suficiente
                        if ($carteira_cliente['saldo'] < $valor_total) {
                            throw new Exception("Erro: Cliente não tem saldo suficiente. Saldo atual: " . 
                                              number_format($carteira_cliente['saldo'], 2) . "€, Valor necessário: " . 
                                              number_format($valor_total, 2) . "€");
                        }

                        // Atualizar saldo do cliente
                        $novo_saldo = $carteira_cliente['saldo'] - $valor_total;
                        $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
                        $stmt = mysqli_prepare($conn, $sql_update);
                        mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira_cliente['id_carteira']);
                        mysqli_stmt_execute($stmt);

                        // Atualizar saldo da empresa
                        $sql_update = "UPDATE carteiras SET saldo = saldo + ? WHERE id_carteira = ?";
                        $stmt = mysqli_prepare($conn, $sql_update);
                        mysqli_stmt_bind_param($stmt, "di", $valor_total, $carteira_empresa['id_carteira']);
                        mysqli_stmt_execute($stmt);

                        // Registrar a transação
                        $sql_trans = "INSERT INTO transacoes (id_carteira_origem, id_carteira_destino, valor, tipo, descricao) 
                                      VALUES (?, ?, ?, 'bilhete', 'Compra de bilhete(s)')";
                        $stmt = mysqli_prepare($conn, $sql_trans);
                        mysqli_stmt_bind_param($stmt, "iid", 
                            $carteira_cliente['id_carteira'], 
                            $carteira_empresa['id_carteira'], 
                            $valor_total
                        );
                        mysqli_stmt_execute($stmt);

                        // Criar os bilhetes
                        for ($i = 0; $i < $quantidade; $i++) {
                            $codigo_bilhete = gerarCodigoBilhete($conn);
                            $lugar = gerarLugarDisponivel($conn, $id_horario, $data_viagem);
                            
                            if (!$lugar) {
                                throw new Exception("Não há lugares suficientes disponíveis.");
                            }
                            
                            $sql_bilhete = "INSERT INTO bilhetes (codigo_bilhete, id_horario, id_utilizador, data_viagem, preco_pago, numero_lugar) 
                                           VALUES (?, ?, ?, ?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $sql_bilhete);
                            mysqli_stmt_bind_param($stmt, "siisdi", 
                                $codigo_bilhete,
                                $id_horario,
                                $id_cliente,
                                $data_viagem,
                                $horario_info['preco'],
                                $lugar
                            );
                            mysqli_stmt_execute($stmt);
                        }

                        // Atualizar lugares disponíveis
                        $sql_update = "UPDATE horarios SET lugares_disponiveis = lugares_disponiveis - ? WHERE id_horario = ?";
                        $stmt = mysqli_prepare($conn, $sql_update);
                        mysqli_stmt_bind_param($stmt, "ii", $quantidade, $id_horario);
                        mysqli_stmt_execute($stmt);

                        // Commit da transação
                        mysqli_commit($conn);
                        
                        // Definir mensagem de sucesso e redirecionar
                        $_SESSION['mensagem'] = "Compra realizada com sucesso! Foram comprados " . $quantidade . " bilhete(s).";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit();

                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $erro = "Erro na transação: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Buscar rotas e horários disponíveis
$sql_rotas = "SELECT r.id_rota, r.origem, r.destino, h.preco, h.id_horario, 
              TIME(h.hora_partida) as hora_partida,
              TIME(h.hora_chegada) as hora_chegada,
              h.lugares_disponiveis
              FROM rotas r 
              JOIN horarios h ON r.id_rota = h.id_rota
              WHERE h.lugares_disponiveis > 0
              ORDER BY TIME(h.hora_partida) ASC";
$result_rotas = mysqli_query($conn, $sql_rotas);

// Buscar clientes
$sql_clientes = "SELECT u.id_utilizador, u.nome_completo, u.email, c.saldo 
                 FROM utilizadores u 
                 LEFT JOIN carteiras c ON u.id_utilizador = c.id_utilizador 
                 WHERE u.perfil = 'cliente'
                 ORDER BY u.nome_completo ASC";
$result_clientes = mysqli_query($conn, $sql_clientes);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Bilhetes - FelixBus</title>
    <link rel="stylesheet" href="gerir_bilhetes.css">
    <style>
        /* Estilos inline para garantir que funcionem */
        .mensagem-container {
            margin: 20px;
            padding: 10px;
        }
        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .mensagem-erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_funcionario.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
        <?php if (isset($_SESSION['id_utilizador'])): ?>
                <?php if ($_SESSION['perfil'] === 'funcionário'): ?>
                    <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
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

    <!-- Adicionar logo após a navegação -->
    <div class="mensagem-container">
        <?php if (!empty($mensagem)): ?>
            <div class="mensagem-sucesso">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="mensagem-erro">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>
    </div>

    <main class="container">
        <h1>Gestão de Bilhetes</h1>

        <section class="ticket-form">
            <h2>Comprar Bilhetes</h2>
            <form method="POST" action="gerir_bilhetes.php">
                <div class="form-group">
                    <label for="id_cliente">Cliente:</label>
                    <select name="id_cliente" id="id_cliente" required>
                        <option value="">Selecione um cliente</option>
                        <?php while ($cliente = mysqli_fetch_assoc($result_clientes)): ?>
                            <option value="<?php echo $cliente['id_utilizador']; ?>">
                                <?php echo htmlspecialchars($cliente['nome_completo']) . 
                                          ' - Saldo: ' . number_format($cliente['saldo'], 2) . '€'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_horario">Rota e Horário:</label>
                    <select name="id_horario" id="id_horario" required>
                        <option value="">Selecione uma rota e horário</option>
                        <?php while ($rota = mysqli_fetch_assoc($result_rotas)): ?>
                            <option value="<?php echo $rota['id_horario']; ?>">
                                <?php echo htmlspecialchars($rota['origem']) . ' → ' . 
                                          htmlspecialchars($rota['destino']) . ' - ' . 
                                          date('H:i', strtotime($rota['hora_partida'])) . 
                                          ' (' . number_format($rota['preco'], 2) . '€)' .
                                          ' - Lugares: ' . $rota['lugares_disponiveis']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data_viagem">Data da Viagem:</label>
                    <input type="date" name="data_viagem" id="data_viagem" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>

                <div class="form-group">
                    <label for="quantidade">Quantidade de Bilhetes:</label>
                    <input type="number" name="quantidade" id="quantidade" required min="1" max="10" value="1">
                </div>

                <button type="submit" class="btn-primary">Comprar Bilhetes</button>
            </form>
        </section>
    </main>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Impedir seleção de datas passadas
    var today = new Date().toISOString().split('T')[0];
    document.getElementById('data_viagem').setAttribute('min', today);
    
    // Limitar reservas até 30 dias no futuro
    var maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);
    document.getElementById('data_viagem').setAttribute('max', maxDate.toISOString().split('T')[0]);
});
</script>

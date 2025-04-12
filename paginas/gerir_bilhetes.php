<?php
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$erro = '';

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
            // Verificar lugares disponíveis
            $sql_lugares = "SELECT lugares_disponiveis FROM horarios WHERE id_horario = ?";
            $stmt = mysqli_prepare($conn, $sql_lugares);
            mysqli_stmt_bind_param($stmt, "i", $id_horario);
            mysqli_stmt_execute($stmt);
            $result_lugares = mysqli_stmt_get_result($stmt);
            $lugares = mysqli_fetch_assoc($result_lugares);

            if ($lugares['lugares_disponiveis'] < $quantidade) {
                $erro = "Erro: Não há lugares suficientes disponíveis. Lugares restantes: " . $lugares['lugares_disponiveis'];
            }
            else {
                // Verificar saldo do cliente
                $sql_carteira = "SELECT c.id_carteira, c.saldo, u.nome_completo 
                               FROM carteiras c 
                               JOIN utilizadores u ON c.id_utilizador = u.id_utilizador 
                               WHERE c.id_utilizador = ?";
                $stmt = mysqli_prepare($conn, $sql_carteira);
                mysqli_stmt_bind_param($stmt, "i", $id_cliente);
                mysqli_stmt_execute($stmt);
                $carteira = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                // Buscar preço do horário
                $sql_preco = "SELECT preco FROM horarios WHERE id_horario = ?";
                $stmt = mysqli_prepare($conn, $sql_preco);
                mysqli_stmt_bind_param($stmt, "i", $id_horario);
                mysqli_stmt_execute($stmt);
                $horario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                $valor_total = $horario['preco'] * $quantidade;

                if (!$carteira) {
                    $erro = "Erro: Cliente não possui carteira registrada.";
                }
                elseif ($carteira['saldo'] < $valor_total) {
                    $erro = sprintf(
                        "Erro: Saldo insuficiente para %s. Saldo atual: %.2f€. Valor total: %.2f€. Faltam: %.2f€",
                        $carteira['nome_completo'],
                        $carteira['saldo'],
                        $valor_total,
                        $valor_total - $carteira['saldo']
                    );
                }
                else {
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Atualizar saldo do cliente
                        $novo_saldo = $carteira['saldo'] - $valor_total;
                        $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
                        $stmt = mysqli_prepare($conn, $sql_update);
                        mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira['id_carteira']);
                        mysqli_stmt_execute($stmt);

                        // Atualizar lugares disponíveis
                        $sql_update_lugares = "UPDATE horarios SET lugares_disponiveis = lugares_disponiveis - ? WHERE id_horario = ?";
                        $stmt = mysqli_prepare($conn, $sql_update_lugares);
                        mysqli_stmt_bind_param($stmt, "ii", $quantidade, $id_horario);
                        mysqli_stmt_execute($stmt);

                        // Buscar informações do horário selecionado
                        $sql_horario_info = "SELECT hora_partida, hora_chegada, preco FROM horarios WHERE id_horario = ?";
                        $stmt = mysqli_prepare($conn, $sql_horario_info);
                        mysqli_stmt_bind_param($stmt, "i", $id_horario);
                        mysqli_stmt_execute($stmt);
                        $horario_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

                        // Extrair hora da viagem do horário selecionado
                        $hora_viagem = date('H:i:s', strtotime($horario_info['hora_partida']));

                        // Registrar bilhetes
                        $codigos_bilhetes = [];
                        for ($i = 0; $i < $quantidade; $i++) {
                            $sql_bilhete = "INSERT INTO bilhetes (id_utilizador, id_horario, data_viagem, hora_viagem, preco_pago) 
                                            VALUES (?, ?, ?, ?, ?)";
                            $stmt = mysqli_prepare($conn, $sql_bilhete);
                            mysqli_stmt_bind_param($stmt, "iissd", 
                                $id_cliente, 
                                $id_horario, 
                                $data_viagem, 
                                $hora_viagem,
                                $horario_info['preco']
                            );
                            mysqli_stmt_execute($stmt);
                            
                            // Obter o código do bilhete gerado
                            $codigo_bilhete = mysqli_insert_id($conn);
                            
                            // Adicionar à mensagem de sucesso
                            $codigos_bilhetes[] = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT codigo_bilhete FROM bilhetes WHERE id_utilizador = $id_cliente ORDER BY data_compra DESC LIMIT 1"
                            ))['codigo_bilhete'];
                        }

                        mysqli_commit($conn);
                        $mensagem = sprintf(
                            "Sucesso! %d bilhete(s) comprado(s) para %s.\nTotal pago: %.2f€\nNovo saldo: %.2f€\n\nCódigos dos bilhetes:\n%s",
                            $quantidade,
                            $carteira['nome_completo'],
                            $valor_total,
                            $novo_saldo,
                            implode("\n", array_map(function($codigo) { 
                                return "- " . substr($codigo, 0, 8) . "..."; 
                            }, $codigos_bilhetes))
                        );
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $erro = "Erro ao processar a compra: " . $e->getMessage() . ". Por favor, tente novamente.";
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
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_funcionario.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
        <a href="gerir_rotas.php" class="nav-link">Gerir Rotas</a>
                    <a href="gerir_utilizadores.php" class="nav-link">Gerir Utilizadores</a>
                    <a href="gerir_alertas.php" class="nav-link">Gerir Alertas</a>
                    <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
                    <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
                    <a href="perfil.php" class="nav-link">Perfil</a>
                    <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Gestão de Bilhetes</h1>

        <?php if ($mensagem): ?>
            <div class="alert success"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert error"><?php echo $erro; ?></div>
        <?php endif; ?>

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






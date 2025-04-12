<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] != 'cliente') {
    header("Location: login.php");
    exit();
}

$id_horario = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;
$id_utilizador = $_SESSION['id_utilizador'];
$mensagem = '';

// Buscar informações do horário
$sql_horario = "SELECT h.*, r.origem, r.destino 
                FROM horarios h 
                JOIN rotas r ON h.id_rota = r.id_rota 
                WHERE h.id_horario = ?";
$stmt = mysqli_prepare($conn, $sql_horario);
mysqli_stmt_bind_param($stmt, "i", $id_horario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$horario = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Verificar saldo do cliente
        $sql_carteira = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
        $stmt = mysqli_prepare($conn, $sql_carteira);
        mysqli_stmt_bind_param($stmt, "i", $id_utilizador);
        mysqli_stmt_execute($stmt);
        $carteira_cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        // 2. Buscar carteira da empresa
        $sql_empresa = "SELECT id_carteira FROM carteiras WHERE tipo = 'empresa' LIMIT 1";
        $result_empresa = mysqli_query($conn, $sql_empresa);
        $carteira_empresa = mysqli_fetch_assoc($result_empresa);

        if ($carteira_cliente['saldo'] >= $horario['preco']) {
            // 3. Atualizar saldo do cliente
            $novo_saldo = $carteira_cliente['saldo'] - $horario['preco'];
            $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
            $stmt = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira_cliente['id_carteira']);
            mysqli_stmt_execute($stmt);

            // 4. Atualizar saldo da empresa
            $sql_update = "UPDATE carteiras SET saldo = saldo + ? WHERE id_carteira = ?";
            $stmt = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt, "di", $horario['preco'], $carteira_empresa['id_carteira']);
            mysqli_stmt_execute($stmt);

            // 5. Registrar a transação
            $sql_trans = "INSERT INTO transacoes (id_carteira_origem, id_carteira_destino, valor, tipo, descricao) 
                         VALUES (?, ?, ?, 'bilhete', 'Compra de bilhete')";
            $stmt = mysqli_prepare($conn, $sql_trans);
            mysqli_stmt_bind_param($stmt, "iid", 
                $carteira_cliente['id_carteira'], 
                $carteira_empresa['id_carteira'], 
                $horario['preco']
            );
            mysqli_stmt_execute($stmt);

            // 6. Criar o bilhete
            $sql_bilhete = "INSERT INTO bilhetes (id_horario, id_utilizador, preco_pago) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_bilhete);
            mysqli_stmt_bind_param($stmt, "iid", $id_horario, $id_utilizador, $horario['preco']);
            mysqli_stmt_execute($stmt);

            // 7. Atualizar lugares disponíveis
            $sql_lugares = "UPDATE horarios SET lugares_disponiveis = lugares_disponiveis - 1 WHERE id_horario = ?";
            $stmt = mysqli_prepare($conn, $sql_lugares);
            mysqli_stmt_bind_param($stmt, "i", $id_horario);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            header("Location: meus_bilhetes.php?success=1");
            exit();
        } else {
            $mensagem = "Saldo insuficiente. Por favor, carregue sua carteira.";
            mysqli_rollback($conn);
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $mensagem = "Erro ao processar a compra. Tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Comprar Bilhete - FelixBus</title>
    <link rel="stylesheet" href="consultar_rotas.css">
</head>
<body>
    <div class="container">
        <h2>Confirmar Compra de Bilhete</h2>
        
        <?php if ($mensagem): ?>
            <div class="alert"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <div class="ticket-details">
            <p><strong>Origem:</strong> <?php echo htmlspecialchars($horario['origem']); ?></p>
            <p><strong>Destino:</strong> <?php echo htmlspecialchars($horario['destino']); ?></p>
            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($horario['hora_partida'])); ?></p>
            <p><strong>Hora Partida:</strong> <?php echo date('H:i', strtotime($horario['hora_partida'])); ?></p>
            <p><strong>Hora Chegada:</strong> <?php echo date('H:i', strtotime($horario['hora_chegada'])); ?></p>
            <p><strong>Preço:</strong> <?php echo number_format($horario['preco'], 2, ',', '.'); ?> €</p>
        </div>

        <form method="POST" class="purchase-form">
            <input type="hidden" name="confirmar_compra" value="1">
            <button type="submit" class="btn-primary">Confirmar Compra</button>
            <a href="consultar_rotas.php" class="btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
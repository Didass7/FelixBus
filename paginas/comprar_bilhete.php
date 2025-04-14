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
                INNER JOIN rotas r ON h.id_rota = r.id_rota 
                WHERE h.id_horario = ?";
$stmt = mysqli_prepare($conn, $sql_horario);
mysqli_stmt_bind_param($stmt, "i", $id_horario);
mysqli_stmt_execute($stmt);
$horario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Verificar se o horário existe
if (!$horario) {
    header("Location: consultar_rotas.php?erro=horario_invalido");
    exit();
}

// Adicionar a função de gerar código de bilhete no início do arquivo
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data_viagem'])) {
    $data_viagem = $_POST['data_viagem'];
    
    try {
        // Validar a data
        $data_atual = date('Y-m-d');
        $data_limite = date('Y-m-d', strtotime('+30 days'));
        
        if ($data_viagem < $data_atual) {
            $mensagem = "Não é possível selecionar uma data passada.";
        } elseif ($data_viagem > $data_limite) {
            $mensagem = "Só é possível comprar bilhetes para os próximos 30 dias.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                // Verificar se há lugares disponíveis
                if ($horario['lugares_disponiveis'] <= 0) {
                    throw new Exception("Não há lugares disponíveis para este horário.");
                }

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

                if ($carteira_cliente['saldo'] < $horario['preco']) {
                    throw new Exception("Saldo insuficiente. Por favor, carregue sua carteira.");
                }

                // 3. Atualizar saldo do cliente
                $novo_saldo = $carteira_cliente['saldo'] - $horario['preco'];
                $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
                $stmt = mysqli_prepare($conn, $sql_update);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar atualização: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira_cliente['id_carteira']);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Erro ao atualizar saldo do cliente.");
                }

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
                $codigo_bilhete = gerarCodigoBilhete($conn);
                $sql_bilhete = "INSERT INTO bilhetes (codigo_bilhete, id_horario, id_utilizador, data_viagem, preco_pago) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql_bilhete);
                mysqli_stmt_bind_param($stmt, "siiss", 
                    $codigo_bilhete,
                    $id_horario, 
                    $id_utilizador, 
                    $data_viagem,
                    $horario['preco']
                );
                mysqli_stmt_execute($stmt);

                // 7. Atualizar lugares disponíveis
                $sql_lugares = "UPDATE horarios SET lugares_disponiveis = lugares_disponiveis - 1 WHERE id_horario = ?";
                $stmt = mysqli_prepare($conn, $sql_lugares);
                mysqli_stmt_bind_param($stmt, "i", $id_horario);
                mysqli_stmt_execute($stmt);

                mysqli_commit($conn);
                header("Location: minhas_viagens.php?success=1");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $mensagem = $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao processar a compra: " . $e->getMessage();
    }
}

// Garantir que a mensagem seja exibida
if (!empty($mensagem)) {
    $_SESSION['mensagem'] = $mensagem;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Comprar Bilhete - FelixBus</title>
    <link rel="stylesheet" href="comprar_bilhete.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_cliente.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>
            <a href="carteira.php" class="nav-link">Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2>Confirmar Compra de Bilhete</h2>
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert">
                <?php 
                    echo htmlspecialchars($_SESSION['mensagem']); 
                    unset($_SESSION['mensagem']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="purchase-form">
            <div class="ticket-details">
                <p><strong>Origem:</strong> <?php echo htmlspecialchars($horario['origem']); ?></p>
                <p><strong>Destino:</strong> <?php echo htmlspecialchars($horario['destino']); ?></p>
                <p>
                    <strong>Data:</strong>
                    <input type="date" name="data_viagem" id="data_viagem" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </p>
                <p><strong>Hora Partida:</strong> <?php echo date('H:i', strtotime($horario['hora_partida'])); ?></p>
                <p><strong>Hora Chegada:</strong> <?php echo date('H:i', strtotime($horario['hora_chegada'])); ?></p>
                <p><strong>Preço:</strong> <?php echo number_format($horario['preco'], 2, ',', '.'); ?> €</p>
                <p><strong>Lugares Disponíveis:</strong> <?php echo $horario['lugares_disponiveis']; ?></p>
            </div>
            <button type="submit" class="btn-primary">Confirmar Compra</button>
        </form>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputData = document.getElementById('data_viagem');
    
    // Definir as datas mínima e máxima
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    const dataMaxima = new Date();
    dataMaxima.setDate(dataMaxima.getDate() + 30);
});
</script>
</html>


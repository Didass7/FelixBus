<?php
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$erro = '';

// Processar operações de carteira
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $operacao = $_POST['operacao'];
    $valor = floatval($_POST['valor']);
    
    // Buscar carteira do cliente
    $sql = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $carteira = mysqli_fetch_assoc($result);

    if ($carteira) {
        if ($operacao === 'deposito') {
            $novo_saldo = $carteira['saldo'] + $valor;
            $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
            $stmt = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira['id_carteira']);
            
            if (mysqli_stmt_execute($stmt)) {
                $mensagem = "Depósito realizado com sucesso!";
            } else {
                $erro = "Erro ao realizar depósito.";
            }
        } elseif ($operacao === 'levantamento') {
            if ($carteira['saldo'] >= $valor) {
                $novo_saldo = $carteira['saldo'] - $valor;
                $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
                $stmt = mysqli_prepare($conn, $sql_update);
                mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira['id_carteira']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $mensagem = "Levantamento realizado com sucesso!";
                } else {
                    $erro = "Erro ao realizar levantamento.";
                }
            } else {
                $erro = "Saldo insuficiente.";
            }
        }
    } else {
        $erro = "Carteira não encontrada.";
    }
}

// Buscar lista de clientes
$sql_clientes = "SELECT u.id_utilizador, u.nome_completo, u.email, c.saldo 
                 FROM utilizadores u 
                 LEFT JOIN carteiras c ON u.id_utilizador = c.id_utilizador 
                 WHERE u.perfil = 'cliente'";
$result_clientes = mysqli_query($conn, $sql_clientes);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Carteiras - FelixBus</title>
    <link rel="stylesheet" href="gerir_carteiras.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_funcionario.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="gerir_carteiras.php" class="nav-link active">Gerir Carteiras</a>
            <a href="gerir_bilhetes.php" class="nav-link">Gerir Bilhetes</a>
            <a href="carteira.php" class="nav-link">Minha Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>Gestão de Carteiras</h1>

        <?php if ($mensagem): ?>
            <div class="alert success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="carteiras-grid">
            <?php while ($cliente = mysqli_fetch_assoc($result_clientes)): ?>
                <div class="carteira-card">
                    <h3><?php echo htmlspecialchars($cliente['nome_completo']); ?></h3>
                    <p>Email: <?php echo htmlspecialchars($cliente['email']); ?></p>
                    <p>Saldo Atual: <?php echo number_format($cliente['saldo'] ?? 0, 2); ?>€</p>
                    
                    <div class="operacoes">
                        <form action="gerir_carteiras.php" method="POST" class="operacao-form">
                            <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_utilizador']; ?>">
                            <input type="hidden" name="operacao" value="deposito">
                            <div class="form-group">
                                <input type="number" name="valor" step="0.01" min="0.01" required placeholder="Valor">
                                <button type="submit" class="btn-deposito">Depositar</button>
                            </div>
                        </form>

                        <form action="gerir_carteiras.php" method="POST" class="operacao-form">
                            <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_utilizador']; ?>">
                            <input type="hidden" name="operacao" value="levantamento">
                            <div class="form-group">
                                <input type="number" name="valor" step="0.01" min="0.01" required placeholder="Valor">
                                <button type="submit" class="btn-levantamento">Levantar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
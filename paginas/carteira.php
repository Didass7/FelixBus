<?php
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilizador = $_SESSION['id_utilizador'];
    $operacao = $_POST['operacao'];
    $valor = floatval($_POST['valor']);
    
    // Get user wallet
    $sql = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_utilizador);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $carteira = mysqli_fetch_assoc($result);
    
    if ($operacao === 'deposito') {
        // Add funds
        $novo_saldo = $carteira['saldo'] + $valor;
        $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
        $stmt = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira['id_carteira']);
        mysqli_stmt_execute($stmt);
        
        // Record transaction
        $sql_trans = "INSERT INTO transacoes (id_carteira_destino, valor, tipo, descricao) 
                     VALUES (?, ?, 'deposito', 'Depósito de fundos')";
        $stmt = mysqli_prepare($conn, $sql_trans);
        mysqli_stmt_bind_param($stmt, "id", $carteira['id_carteira'], $valor);
        mysqli_stmt_execute($stmt);
    } elseif ($operacao === 'levantamento') {
        if ($carteira['saldo'] >= $valor) {
            // Subtract funds
            $novo_saldo = $carteira['saldo'] - $valor;
            $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
            $stmt = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira['id_carteira']);
            mysqli_stmt_execute($stmt);
            
            // Record transaction
            $sql_trans = "INSERT INTO transacoes (id_carteira_origem, valor, tipo, descricao) 
                         VALUES (?, ?, 'levantamento', 'Levantamento de fundos')";
            $stmt = mysqli_prepare($conn, $sql_trans);
            mysqli_stmt_bind_param($stmt, "id", $carteira['id_carteira'], $valor);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Check if user has a wallet, if not create one
$sql_check = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
$stmt = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_utilizador']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$carteira = mysqli_fetch_assoc($result);

if (!$carteira) {
    // Create wallet for user
    $sql_create = "INSERT INTO carteiras (id_utilizador, tipo, saldo) VALUES (?, 'cliente', 0.00)";
    $stmt = mysqli_prepare($conn, $sql_create);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_utilizador']);
    mysqli_stmt_execute($stmt);
    
    // Get the newly created wallet
    $sql_get = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql_get);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_utilizador']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $carteira = mysqli_fetch_assoc($result);
}

// Now $carteira will always exist and have a saldo value
// Get recent transactions (using correct column name data_operacao)
$sql = "SELECT * FROM transacoes 
        WHERE id_carteira_origem = ? OR id_carteira_destino = ?
        ORDER BY data_operacao DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $carteira['id_carteira'], $carteira['id_carteira']);
mysqli_stmt_execute($stmt);
$transacoes = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Gestão de Carteira</title>
    <link rel="stylesheet" href="carteira.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <a href="pagina_inicial_cliente.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <a href="#rotas" class="nav-link">Rotas</a>
            <a href="#horarios" class="nav-link">Horários</a>
            <a href="carteira.php" class="nav-link">Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="wallet-container">
        <section class="balance-section">
            <h1>Sua Carteira</h1>
            <div class="balance-card">
                <h2>Saldo Atual</h2>
                <p class="balance-amount"><?php echo number_format($carteira['saldo'], 2); ?>€</p>
            </div>
        </section>

        <section class="operations-section">
            <div class="operation-card">
                <h2>Depositar Fundos</h2>
                <form action="carteira.php" method="POST" class="operation-form">
                    <input type="hidden" name="operacao" value="deposito">
                    <div class="form-group">
                        <label for="valor-deposito">Valor a Depositar (€):</label>
                        <input type="number" id="valor-deposito" name="valor" min="0.01" step="0.01" required>
                    </div>
                    <button type="submit" class="btn-deposit">Depositar</button>
                </form>
            </div>

            <div class="operation-card">
                <h2>Levantar Fundos</h2>
                <form action="carteira.php" method="POST" class="operation-form">
                    <input type="hidden" name="operacao" value="levantamento">
                    <div class="form-group">
                        <label for="valor-levantamento">Valor a Levantar (€):</label>
                        <input type="number" id="valor-levantamento" name="valor" min="0.01" step="0.01" required>
                    </div>
                    <button type="submit" class="btn-withdraw">Levantar</button>
                </form>
            </div>
        </section>

        <section class="transactions-section">
            <h2>Histórico de Transações</h2>
            <div class="transactions-table">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($transacao = mysqli_fetch_assoc($transacoes)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($transacao['data_operacao'])); ?></td>
                            <td><?php echo ucfirst($transacao['tipo']); ?></td>
                            <td class="<?php echo $transacao['tipo'] === 'deposito' ? 'positive' : 'negative'; ?>">
                                <?php echo ($transacao['tipo'] === 'deposito' ? '+' : '-') . number_format($transacao['valor'], 2); ?>€
                            </td>
                            <td><?php echo htmlspecialchars($transacao['descricao']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>
        
        <div class="footer-links">
            <a href="#" class="footer-link">Sobre Nós</a>
            <a href="#" class="footer-link">Contactos</a>
            <a href="#" class="footer-link">Termos</a>
        </div>
        
        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>



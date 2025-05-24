<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o utilizador está autenticado
if (!isset($_SESSION['id_utilizador'])) {
    header("Location: login.php");
    exit();
}

// Redirecionar funcionários e administradores para as suas páginas iniciais
if ($_SESSION['perfil'] === 'funcionário') {
    header("Location: pagina_inicial_funcionario.php");
    exit();
} elseif ($_SESSION['perfil'] === 'administrador') {
    header("Location: pagina_inicial_admin.php");
    exit();
}

// Processar operações de depósito ou levantamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilizador = $_SESSION['id_utilizador'];
    $operacao = $_POST['operacao'];
    $valor = (float)$_POST['valor'];

    // Obter dados da carteira do utilizador
    $sql = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_utilizador);
    $stmt->execute();
    $result = $stmt->get_result();
    $carteira = $result->fetch_assoc();
    $stmt->close();

    if ($operacao === 'deposito') {
        // Adicionar fundos à carteira
        $novo_saldo = $carteira['saldo'] + $valor;
        $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("di", $novo_saldo, $carteira['id_carteira']);
        $stmt->execute();
        $stmt->close();

        // Registar a transação
        $sql_trans = "INSERT INTO transacoes (id_carteira_destino, valor, tipo, descricao)
                      VALUES (?, ?, 'deposito', 'Depósito de fundos')";
        $stmt = $conn->prepare($sql_trans);
        $stmt->bind_param("id", $carteira['id_carteira'], $valor);
        $stmt->execute();
        $stmt->close();
    } elseif ($operacao === 'levantamento') {
        // Verificar se há saldo suficiente
        if ($carteira['saldo'] >= $valor) {
            // Subtrair fundos da carteira
            $novo_saldo = $carteira['saldo'] - $valor;
            $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("di", $novo_saldo, $carteira['id_carteira']);
            $stmt->execute();
            $stmt->close();

            // Registar a transação
            $sql_trans = "INSERT INTO transacoes (id_carteira_origem, valor, tipo, descricao)
                          VALUES (?, ?, 'levantamento', 'Levantamento de fundos')";
            $stmt = $conn->prepare($sql_trans);
            $stmt->bind_param("id", $carteira['id_carteira'], $valor);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Verificar se o utilizador tem carteira, caso não tenha, criar uma
$sql_check = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $_SESSION['id_utilizador']);
$stmt->execute();
$result = $stmt->get_result();
$carteira = $result->fetch_assoc();
$stmt->close();

if (!$carteira) {
    // Criar carteira para o utilizador
    $sql_create = "INSERT INTO carteiras (id_utilizador, tipo, saldo) VALUES (?, 'cliente', 0.00)";
    $stmt = $conn->prepare($sql_create);
    $stmt->bind_param("i", $_SESSION['id_utilizador']);
    $stmt->execute();
    $stmt->close();

    // Obter a carteira recém-criada
    $sql_get = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
    $stmt = $conn->prepare($sql_get);
    $stmt->bind_param("i", $_SESSION['id_utilizador']);
    $stmt->execute();
    $result = $stmt->get_result();
    $carteira = $result->fetch_assoc();
    $stmt->close();
}

// Obter transações recentes
$sql = "SELECT * FROM transacoes
        WHERE id_carteira_origem = ? OR id_carteira_destino = ?
        ORDER BY data_operacao DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $carteira['id_carteira'], $carteira['id_carteira']);
$stmt->execute();
$transacoes = $stmt->get_result();
$stmt->close();
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
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
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

    <main class="wallet-container">
        <section class="balance-section">
            <h1>A Sua Carteira</h1>
            <div class="balance-card">
                <h2>Saldo Atual</h2>
                <p class="balance-amount"><?php echo htmlspecialchars(number_format($carteira['saldo'], 2)); ?>€</p>
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
                        <?php while ($transacao = $transacoes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($transacao['data_operacao']))); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($transacao['tipo'])); ?></td>
                            <td class="<?php echo $transacao['tipo'] === 'deposito' ? 'positive' : 'negative'; ?>">
                                <?php echo htmlspecialchars(($transacao['tipo'] === 'deposito' ? '+' : '-') . number_format($transacao['valor'], 2)); ?>€
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
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <p>&copy; 2024 FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

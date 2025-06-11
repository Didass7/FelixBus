<?php
// inicializa a sessão e verifica permissões de acesso do utilizador
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

// inicializa variáveis para mensagens e saldo da empresa
$mensagem = '';
$erro = '';
$saldo_empresa = 0;

// obtém o saldo total da empresa a partir da base de dados
$result_empresa = $conn->query("SELECT saldo FROM carteiras WHERE tipo = 'empresa' LIMIT 1");
if ($empresa = $result_empresa->fetch_assoc()) {
    $saldo_empresa = $empresa['saldo'];
}
$result_empresa->close();

// processa operações de depósito ou levantamento submetidas pelo formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $operacao = $_POST['operacao'];
    $valor = (double)$_POST['valor'];

    // verifica se a carteira do cliente existe
    $stmt = $conn->prepare("SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    $carteira = $result->fetch_assoc();
    $stmt->close();

    if (!$carteira) {
        $erro = "Carteira não encontrada.";
    } else {
        // realiza depósito na carteira do cliente
        if ($operacao === 'deposito') {
            $novo_saldo = $carteira['saldo'] + $valor;
            $stmt = $conn->prepare("UPDATE carteiras SET saldo = ? WHERE id_carteira = ?");
            $stmt->bind_param("di", $novo_saldo, $carteira['id_carteira']);

            if ($stmt->execute()) {
                $mensagem = "Depósito realizado com sucesso!";
            } else {
                $erro = "Erro ao realizar depósito.";
            }
            $stmt->close();
        }
        // realiza levantamento se o saldo for suficiente
        elseif ($operacao === 'levantamento') {
            if ($carteira['saldo'] >= $valor) {
                $novo_saldo = $carteira['saldo'] - $valor;
                $stmt = $conn->prepare("UPDATE carteiras SET saldo = ? WHERE id_carteira = ?");
                $stmt->bind_param("di", $novo_saldo, $carteira['id_carteira']);

                if ($stmt->execute()) {
                    $mensagem = "Levantamento realizado com sucesso!";
                } else {
                    $erro = "Erro ao realizar levantamento.";
                }
                $stmt->close();
            } else {
                $erro = "Saldo insuficiente.";
            }
        }
    }
}

// obtém a lista de clientes e os respetivos saldos
$sql_clientes = "SELECT u.id_utilizador, u.nome_completo, u.email, c.saldo
                FROM utilizadores u
                LEFT JOIN carteiras c ON u.id_utilizador = c.id_utilizador
                WHERE u.perfil = 'cliente'";
$result_clientes = $conn->query($sql_clientes);
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
    <!-- barra de navegação com links para diferentes áreas do sistema -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>
        <div class="nav-links">
            <?php if ($_SESSION['perfil'] === 'funcionário'): ?>
                <a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>
            <?php else: ?>
                <a href="pagina_inicial_admin.php" class="nav-link">Painel de Administração</a>
            <?php endif; ?>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </nav>

    <!-- exibe o saldo total da empresa -->
    <main class="container">
        <div class="empresa-carteira">
            <div class="carteira-empresa-card">
                <h2>Carteira da Empresa</h2>
                <div class="saldo-valor">
                    <span class="saldo-label">Saldo Total:</span>
                    <span class="saldo-amount"><?php echo htmlspecialchars(number_format($saldo_empresa, 2)); ?>€</span>
                </div>
            </div>
        </div>

        <h1>Gestão de Carteiras</h1>

        <!-- exibe mensagens de sucesso ou erro -->
        <?php if ($mensagem): ?>
            <div class="alert success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <!-- lista as carteiras dos clientes com opções de depósito e levantamento -->
        <div class="carteiras-grid">
            <?php while ($cliente = $result_clientes->fetch_assoc()): ?>
                <div class="carteira-card">
                    <h3><?php echo htmlspecialchars($cliente['nome_completo']); ?></h3>
                    <p>Email: <?php echo htmlspecialchars($cliente['email']); ?></p>
                    <p>Saldo Atual: <?php echo htmlspecialchars(number_format($cliente['saldo'] ?? 0, 2)); ?>€</p>

                    <div class="operacoes">
                        <!-- formulário para realizar depósito -->
                        <form action="gerir_carteiras.php" method="POST" class="operacao-form">
                            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_utilizador']); ?>">
                            <input type="hidden" name="operacao" value="deposito">
                            <div class="form-group">
                                <input type="number" name="valor" step="0.01" min="0.01" required placeholder="Valor">
                                <button type="submit" class="btn-deposito">Depositar</button>
                            </div>
                        </form>

                        <!-- formulário para realizar levantamento -->
                        <form action="gerir_carteiras.php" method="POST" class="operacao-form">
                            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_utilizador']); ?>">
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

    <!-- rodapé com links úteis e redes sociais -->
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

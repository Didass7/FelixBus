<?php
/**
 * Sistema de Gestão de Carteiras
 *
 * Este ficheiro permite que funcionários e administradores gerenciem as carteiras dos clientes,
 * realizando operações de depósito e levantamento.
 *
 * @author FelixBus
 * @version 1.0
 */

session_start();
include '../basedados/basedados.h';

// Verificar permissões de acesso
if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

// Inicializar variáveis
$mensagem = '';
$erro = '';
$saldo_empresa = 0;

// Obter saldo da empresa
$result_empresa = $conn->query("SELECT saldo FROM carteiras WHERE tipo = 'empresa' LIMIT 1");
if ($empresa = $result_empresa->fetch_assoc()) {
    $saldo_empresa = $empresa['saldo'];
}
$result_empresa->close();

// Processar operações de carteira (depósito ou levantamento)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $id_cliente = $_POST['id_cliente'];
    $operacao = $_POST['operacao'];
    $valor = (double)$_POST['valor'];

    // Obter carteira do cliente
    $stmt = $conn->prepare("SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    $carteira = $result->fetch_assoc();
    $stmt->close();

    // Verificar se a carteira existe
    if (!$carteira) {
        $erro = "Carteira não encontrada.";
    } else {
        // Processar depósito
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
        // Processar levantamento
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

// Obter lista de clientes com seus saldos
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
    <!-- Barra de navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="<?php echo $_SESSION['perfil'] === 'administrador' ? 'pagina_inicial_admin.php' : 'pagina_inicial_funcionario.php'; ?>">
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

    <!-- Conteúdo principal -->
    <main class="container">
        <!-- Carteira da empresa -->
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

        <!-- Mensagens de alerta -->
        <?php if ($mensagem): ?>
            <div class="alert success"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>
        <?php if ($erro): ?>
            <div class="alert error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <!-- Lista de carteiras dos clientes -->
        <div class="carteiras-grid">
            <?php while ($cliente = $result_clientes->fetch_assoc()): ?>
                <div class="carteira-card">
                    <h3><?php echo htmlspecialchars($cliente['nome_completo']); ?></h3>
                    <p>Email: <?php echo htmlspecialchars($cliente['email']); ?></p>
                    <p>Saldo Atual: <?php echo htmlspecialchars(number_format($cliente['saldo'] ?? 0, 2)); ?>€</p>

                    <div class="operacoes">
                        <!-- Formulário de depósito -->
                        <form action="gerir_carteiras.php" method="POST" class="operacao-form">
                            <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_utilizador']); ?>">
                            <input type="hidden" name="operacao" value="deposito">
                            <div class="form-group">
                                <input type="number" name="valor" step="0.01" min="0.01" required placeholder="Valor">
                                <button type="submit" class="btn-deposito">Depositar</button>
                            </div>
                        </form>

                        <!-- Formulário de levantamento -->
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

    <!-- Rodapé -->
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

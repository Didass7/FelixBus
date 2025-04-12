<?php
session_start();
include '../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || ($_SESSION['perfil'] !== 'funcionário' && $_SESSION['perfil'] !== 'administrador')) {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$erro = '';

// Buscar rotas disponíveis
$sql_rotas = "SELECT * FROM rotas WHERE ativo = 1";
$result_rotas = mysqli_query($conn, $sql_rotas);

// Buscar clientes
$sql_clientes = "SELECT id_utilizador, nome_completo, email FROM utilizadores WHERE perfil = 'cliente'";
$result_clientes = mysqli_query($conn, $sql_clientes);

// Processar compra de bilhete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $id_rota = $_POST['id_rota'];
    $data_viagem = $_POST['data_viagem'];
    $quantidade = $_POST['quantidade'];
    
    // Verificar saldo do cliente
    $sql_carteira = "SELECT id_carteira, saldo FROM carteiras WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql_carteira);
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $carteira = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Buscar preço da rota
    $sql_preco = "SELECT preco FROM rotas WHERE id_rota = ?";
    $stmt = mysqli_prepare($conn, $sql_preco);
    mysqli_stmt_bind_param($stmt, "i", $id_rota);
    mysqli_stmt_execute($stmt);
    $rota = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $valor_total = $rota['preco'] * $quantidade;

    if ($carteira && $carteira['saldo'] >= $valor_total) {
        mysqli_begin_transaction($conn);
        
        try {
            // Atualizar saldo do cliente
            $novo_saldo = $carteira['saldo'] - $valor_total;
            $sql_update = "UPDATE carteiras SET saldo = ? WHERE id_carteira = ?";
            $stmt = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt, "di", $novo_saldo, $carteira['id_carteira']);
            mysqli_stmt_execute($stmt);

            // Registrar bilhetes
            for ($i = 0; $i < $quantidade; $i++) {
                $sql_bilhete = "INSERT INTO bilhetes (id_utilizador, id_rota, data_viagem, preco) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql_bilhete);
                mysqli_stmt_bind_param($stmt, "iiss", $id_cliente, $id_rota, $data_viagem, $rota['preco']);
                mysqli_stmt_execute($stmt);
            }

            mysqli_commit($conn);
            $mensagem = "Bilhetes comprados com sucesso!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $erro = "Erro ao processar a compra. Por favor, tente novamente.";
        }
    } else {
        $erro = "Saldo insuficiente para realizar a compra.";
    }
}
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
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="gerir_carteiras.php" class="nav-link">Gerir Carteiras</a>
            <a href="gerir_bilhetes.php" class="nav-link active">Gerir Bilhetes</a>
            <a href="carteira.php" class="nav-link">Minha Carteira</a>
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
                                <?php echo $cliente['nome_completo'] . ' (' . $cliente['email'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_rota">Rota:</label>
                    <select name="id_rota" id="id_rota" required>
                        <option value="">Selecione uma rota</option>
                        <?php while ($rota = mysqli_fetch_assoc($result_rotas)): ?>
                            <option value="<?php echo $rota['id_rota']; ?>">
                                <?php echo $rota['origem'] . ' → ' . $rota['destino'] . ' (' . $rota['preco'] . '€)'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data_viagem">Data da Viagem:</label>
                    <input type="date" name="data_viagem" id="data_viagem" required min="<?php echo date('Y-m-d'); ?>">
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

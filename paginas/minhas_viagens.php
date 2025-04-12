<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] != 'cliente') {
    header("Location: login.php");
    exit();
}

$id_utilizador = $_SESSION['id_utilizador'];

// Buscar viagens do usuário
$sql = "SELECT b.*, h.hora_partida, h.hora_chegada, r.origem, r.destino 
        FROM bilhetes b 
        JOIN horarios h ON b.id_horario = h.id_horario 
        JOIN rotas r ON h.id_rota = r.id_rota 
        WHERE b.id_utilizador = ? 
        ORDER BY h.hora_partida DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_utilizador);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$viagens = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Minhas Viagens - FelixBus</title>
    <link rel="stylesheet" href="minhas_viagens.css">
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
            <a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>
            <a href="minhas_viagens.php" class="nav-link active">Minhas Viagens</a>
            <a href="carteira.php" class="nav-link">Carteira</a>
            <a href="perfil.php" class="nav-link">Perfil</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2>Minhas Viagens</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            Viagem reservada com sucesso!
        </div>
        <?php endif; ?>

        <?php if (empty($viagens)): ?>
            <div class="no-trips">
                <p>Você ainda não tem viagens reservadas.</p>
                <a href="consultar_rotas.php" class="btn-primary">Ver Rotas Disponíveis</a>
            </div>
        <?php else: ?>
            <div class="trips-grid">
                <?php foreach ($viagens as $viagem): ?>
                    <div class="trip-card">
                        <div class="trip-header">
                            <h3><?php echo htmlspecialchars($viagem['origem']); ?> → <?php echo htmlspecialchars($viagem['destino']); ?></h3>
                        </div>
                        <div class="trip-body">
                            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($viagem['hora_partida'])); ?></p>
                            <p><strong>Partida:</strong> <?php echo date('H:i', strtotime($viagem['hora_partida'])); ?></p>
                            <p><strong>Chegada:</strong> <?php echo date('H:i', strtotime($viagem['hora_chegada'])); ?></p>
                            <p><strong>Preço:</strong> <?php echo number_format($viagem['preco_pago'], 2, ',', '.'); ?> €</p>
                            <?php if (strtotime($viagem['hora_partida']) > time()): ?>
                                <div class="trip-actions">
                                    <a href="cancelar_viagem.php?id=<?php echo $viagem['id_bilhete']; ?>" class="btn-secondary" onclick="return confirm('Tem certeza que deseja cancelar esta viagem?')">Cancelar Viagem</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
</html>
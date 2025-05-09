<?php
session_start();
include '../basedados/basedados.h';

// Verifica se o utilizador está autenticado e é cliente
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] != 'cliente') {
    header("Location: login.php");
    exit();
}

$id_utilizador = $_SESSION['id_utilizador'];

// Obtém as viagens do utilizador com informações detalhadas
$sql = "SELECT
            b.codigo_bilhete,
            b.data_viagem,
            b.preco_pago,
            b.numero_lugar,
            h.hora_partida,
            h.hora_chegada,
            TIME(h.hora_partida) as hora_partida_time,
            TIME(h.hora_chegada) as hora_chegada_time,
            r.origem,
            r.destino
        FROM bilhetes b
        JOIN horarios h ON b.id_horario = h.id_horario
        JOIN rotas r ON h.id_rota = r.id_rota
        WHERE b.id_utilizador = ?
        ORDER BY b.data_viagem DESC, h.hora_partida DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_utilizador);
$stmt->execute();
$result = $stmt->get_result();
$viagens = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Viagens - FelixBus</title>
    <link rel="stylesheet" href="minhas_viagens.css">
</head>
<body>
    <!-- Barra de Navegação -->
    <nav class="navbar">
        <div class="logo">
            <a href="<?php echo $_SESSION['perfil'] === 'cliente' ? 'pagina_inicial_cliente.php' :
                           ($_SESSION['perfil'] === 'funcionário' ? 'pagina_inicial_funcionario.php' :
                           ($_SESSION['perfil'] === 'administrador' ? 'pagina_inicial_admin.php' : 'index.php')); ?>">
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
        <h2>Minhas Viagens</h2>

        <!-- Mensagens de Sistema -->
        <?php if (isset($_GET['cancelamento']) && $_GET['cancelamento'] == 'sucesso'): ?>
            <div class="success-message">
                Viagem cancelada com sucesso! O valor foi reembolsado para sua carteira.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div class="error-message">
                <?php
                $mensagens_erro = [
                    'nao_encontrado' => "Bilhete não encontrado no sistema.",
                    'partida' => "Não é possível cancelar esta viagem pois já partiu.",
                    'limite_tempo' => "Não é possível cancelar a viagem com menos de 1 hora de antecedência.",
                    'sistema' => "Ocorreu um erro no sistema. Por favor, tente novamente."
                ];
                echo $mensagens_erro[$_GET['erro']] ?? "Erro ao processar o cancelamento. Por favor, tente novamente.";
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                Viagem reservada com sucesso!
            </div>
        <?php endif; ?>

        <!-- Lista de Viagens -->
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
                            <p class="ticket-code"><strong>Código do Bilhete:</strong> <?php echo htmlspecialchars($viagem['codigo_bilhete']); ?></p>
                            <p><strong>Data:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($viagem['data_viagem']))); ?></p>
                            <p><strong>Partida:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($viagem['hora_partida_time']))); ?></p>
                            <p><strong>Chegada:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($viagem['hora_chegada_time']))); ?></p>
                            <p><strong>Lugar:</strong> <?php echo htmlspecialchars($viagem['numero_lugar']); ?></p>
                            <p><strong>Preço:</strong> <?php echo htmlspecialchars(number_format($viagem['preco_pago'], 2, ',', '.')); ?> €</p>

                            <?php
                            // Verifica se a viagem ainda não partiu para mostrar opção de cancelamento
                            $data_hora_partida = date('Y-m-d H:i:s', strtotime($viagem['data_viagem'] . ' ' . $viagem['hora_partida_time']));
                            if (strtotime($data_hora_partida) > time()):
                            ?>
                                <div class="trip-actions">
                                    <a href="cancelar_viagem.php?id=<?php echo htmlspecialchars($viagem['codigo_bilhete']); ?>"
                                       class="btn-secondary"
                                       onclick="return confirm('Tem certeza que deseja cancelar esta viagem?')">
                                        Cancelar Viagem
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

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

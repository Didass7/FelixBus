<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o utilizador está autenticado e tem perfil de cliente
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] != 'cliente') {
    header("Location: login.php");
    exit();
}

// Verificar se o ID do bilhete foi fornecido
if (!isset($_GET['id'])) {
    header("Location: minhas_viagens.php");
    exit();
}

$codigo_bilhete = $_GET['id'];
$id_utilizador = $_SESSION['id_utilizador'];

try {
    // Iniciar transação na base de dados
    mysqli_begin_transaction($conn);

    // Obter informações do bilhete e do horário associado
    $sql_verificar = "SELECT b.*, h.hora_partida, b.preco_pago,
                      b.data_viagem, TIME(h.hora_partida) as hora_partida_time,
                      h.id_horario
                      FROM bilhetes b
                      JOIN horarios h ON b.id_horario = h.id_horario
                      WHERE b.codigo_bilhete = ? AND b.id_utilizador = ?";

    $stmt = mysqli_prepare($conn, $sql_verificar);
    mysqli_stmt_bind_param($stmt, "si", $codigo_bilhete, $id_utilizador);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($bilhete = mysqli_fetch_assoc($result)) {
        // Calcular o tempo limite para cancelamento (1 hora antes da partida)
        $data_hora_partida = $bilhete['data_viagem'] . ' ' . $bilhete['hora_partida_time'];
        $tempo_partida = strtotime($data_hora_partida);
        $tempo_atual = time();
        $tempo_limite = $tempo_partida - (60 * 60); // 1 hora em segundos

        if ($tempo_atual < $tempo_limite) {
            // Atualizar lugares disponíveis na viagem
            $sql_lugares = "UPDATE viagens_diarias
                           SET lugares_disponiveis = lugares_disponiveis + 1
                           WHERE id_horario = ? AND data_viagem = ?";
            $stmt = mysqli_prepare($conn, $sql_lugares);
            mysqli_stmt_bind_param($stmt, "is", $bilhete['id_horario'], $bilhete['data_viagem']);
            mysqli_stmt_execute($stmt);

            // Reembolsar o valor na carteira do cliente
            $sql_carteira = "UPDATE carteiras
                            SET saldo = saldo + ?
                            WHERE id_utilizador = ?";
            $stmt = mysqli_prepare($conn, $sql_carteira);
            mysqli_stmt_bind_param($stmt, "di", $bilhete['preco_pago'], $id_utilizador);
            mysqli_stmt_execute($stmt);

            // Remover o bilhete da base de dados
            $sql_deletar = "DELETE FROM bilhetes WHERE codigo_bilhete = ?";
            $stmt = mysqli_prepare($conn, $sql_deletar);
            mysqli_stmt_bind_param($stmt, "s", $codigo_bilhete);
            mysqli_stmt_execute($stmt);

            // Confirmar todas as alterações
            mysqli_commit($conn);
            header("Location: minhas_viagens.php?cancelamento=sucesso");
            exit();
        } else {
            // Não é possível cancelar (menos de 1 hora para a partida)
            mysqli_rollback($conn);
            header("Location: minhas_viagens.php?erro=limite_tempo");
            exit();
        }
    } else {
        // Bilhete não encontrado ou não pertence ao utilizador
        mysqli_rollback($conn);
        header("Location: minhas_viagens.php?erro=nao_encontrado");
        exit();
    }
} catch (Exception $e) {
    // Reverter alterações em caso de erro
    mysqli_rollback($conn);
    error_log("Erro ao cancelar bilhete: " . $e->getMessage());
    header("Location: minhas_viagens.php?erro=sistema");
    exit();
}
?>

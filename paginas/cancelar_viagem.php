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
    // Obter informações do bilhete e do horário associado
    $sql_verificar = "SELECT b.*, h.hora_partida, b.preco_pago,
                      b.data_viagem, TIME(h.hora_partida) as hora_partida_time,
                      h.id_horario
                      FROM bilhetes b
                      JOIN horarios h ON b.id_horario = h.id_horario
                      WHERE b.codigo_bilhete = ? AND b.id_utilizador = ?";

    $stmt = $conn->prepare($sql_verificar);

    if (!$stmt) {
        throw new Exception("Erro ao preparar a consulta de verificação: " . mysqli_error($conn));
    }

    $stmt->bind_param("si", $codigo_bilhete, $id_utilizador);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar a consulta de verificação: {$stmt->error}");
    }

    $result = $stmt->get_result();
    $bilhete = $result->fetch_assoc();
    $stmt->close();

    if ($bilhete) {
        // Calcular o tempo limite para cancelamento (1 hora antes da partida)
        $data_hora_partida = $bilhete['data_viagem'] . ' ' . $bilhete['hora_partida_time'];
        $tempo_partida = strtotime($data_hora_partida);
        $tempo_atual = time();
        $tempo_limite = $tempo_partida - 60 * 60; // 1 hora em segundos

        if ($tempo_atual < $tempo_limite) {
            // Atualizar lugares disponíveis na viagem
            $sql_lugares = "UPDATE viagens_diarias
                           SET lugares_disponiveis = lugares_disponiveis + 1
                           WHERE id_horario = ? AND data_viagem = ?";
            $stmt = $conn->prepare($sql_lugares);

            if (!$stmt) {
                throw new Exception("Erro ao preparar a atualização de lugares: " . mysqli_error($conn));
            }

            $stmt->bind_param("is", $bilhete['id_horario'], $bilhete['data_viagem']);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar lugares disponíveis: {$stmt->error}");
            }
            $stmt->close();

            // Reembolsar o valor na carteira do cliente
            $sql_carteira = "UPDATE carteiras
                            SET saldo = saldo + ?
                            WHERE id_utilizador = ?";
            $stmt = $conn->prepare($sql_carteira);

            if (!$stmt) {
                throw new Exception("Erro ao preparar o reembolso: " . mysqli_error($conn));
            }

            $stmt->bind_param("di", $bilhete['preco_pago'], $id_utilizador);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao reembolsar valor: {$stmt->error}");
            }
            $stmt->close();

            // Remover o bilhete da base de dados
            $sql_deletar = "DELETE FROM bilhetes WHERE codigo_bilhete = ?";
            $stmt = $conn->prepare($sql_deletar);

            if (!$stmt) {
                throw new Exception("Erro ao preparar a remoção do bilhete: " . mysqli_error($conn));
            }

            $stmt->bind_param("s", $codigo_bilhete);

            if (!$stmt->execute()) {
                throw new Exception("Erro ao remover bilhete: {$stmt->error}");
            }
            $stmt->close();

            header("Location: minhas_viagens.php?cancelamento=sucesso");
            exit();
        } else {
            // Não é possível cancelar (menos de 1 hora para a partida)
            header("Location: minhas_viagens.php?erro=limite_tempo");
            exit();
        }
    } else {
        // Bilhete não encontrado ou não pertence ao utilizador
        header("Location: minhas_viagens.php?erro=nao_encontrado");
        exit();
    }
} catch (Exception $e) {
    // Registrar erro e redirecionar
    error_log("Erro ao cancelar bilhete: " . $e->getMessage());
    header("Location: minhas_viagens.php?erro=sistema");
    exit();
}

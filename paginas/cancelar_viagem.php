<?php
session_start();
include '../basedados/basedados.h';

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] != 'cliente') {
    header("Location: login.php");
    exit();
}

// Verificar se foi fornecido um ID de bilhete
if (!isset($_GET['id'])) {
    header("Location: minhas_viagens.php");
    exit();
}

$codigo_bilhete = $_GET['id'];
$id_utilizador = $_SESSION['id_utilizador'];

try {
    // Iniciar transação
    mysqli_begin_transaction($conn);

    // Primeiro, vamos verificar se o bilhete existe e pegar informações do horário
    $sql_verificar = "SELECT b.*, h.hora_partida, b.preco_pago,
                            b.data_viagem,
                            TIME(h.hora_partida) as hora_partida_time,
                            h.id_horario,
                            h.lugares_disponiveis,
                            h.capacidade_autocarro
                     FROM bilhetes b 
                     JOIN horarios h ON b.id_horario = h.id_horario 
                     WHERE b.codigo_bilhete = ? 
                     AND b.id_utilizador = ?";
    
    $stmt = mysqli_prepare($conn, $sql_verificar);
    mysqli_stmt_bind_param($stmt, "si", $codigo_bilhete, $id_utilizador);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($bilhete = mysqli_fetch_assoc($result)) {
        $data_hora_partida = $bilhete['data_viagem'] . ' ' . $bilhete['hora_partida_time'];
        $tempo_partida = strtotime($data_hora_partida);
        $tempo_atual = time();
        
        if ($tempo_partida > $tempo_atual) {
            // 1. Atualizar lugares disponíveis
            $sql_lugares = "UPDATE horarios 
                          SET lugares_disponiveis = lugares_disponiveis + 1 
                          WHERE id_horario = ?";
            $stmt = mysqli_prepare($conn, $sql_lugares);
            mysqli_stmt_bind_param($stmt, "i", $bilhete['id_horario']);
            mysqli_stmt_execute($stmt);

            // Verificar se a atualização dos lugares foi bem-sucedida
            if (mysqli_affected_rows($conn) <= 0) {
                throw new Exception("Erro ao atualizar lugares disponíveis");
            }

            // 2. Reembolsar o valor na carteira do cliente
            $sql_carteira = "UPDATE carteiras 
                           SET saldo = saldo + ? 
                           WHERE id_utilizador = ?";
            $stmt = mysqli_prepare($conn, $sql_carteira);
            mysqli_stmt_bind_param($stmt, "di", $bilhete['preco_pago'], $id_utilizador);
            mysqli_stmt_execute($stmt);

            if (mysqli_affected_rows($conn) <= 0) {
                throw new Exception("Erro ao reembolsar valor");
            }

            // 3. Deletar o bilhete
            $sql_deletar = "DELETE FROM bilhetes WHERE codigo_bilhete = ?";
            $stmt = mysqli_prepare($conn, $sql_deletar);
            mysqli_stmt_bind_param($stmt, "s", $codigo_bilhete);
            mysqli_stmt_execute($stmt);

            if (mysqli_affected_rows($conn) <= 0) {
                throw new Exception("Erro ao deletar bilhete");
            }

            // Commit da transação
            mysqli_commit($conn);
            
            // Redirecionar com mensagem de sucesso
            header("Location: minhas_viagens.php?cancelamento=sucesso");
            exit();
        } else {
            // A viagem já partiu
            mysqli_rollback($conn);
            header("Location: minhas_viagens.php?erro=partida");
            exit();
        }
    } else {
        // Bilhete não encontrado
        mysqli_rollback($conn);
        header("Location: minhas_viagens.php?erro=nao_encontrado");
        exit();
    }
} catch (Exception $e) {
    // Em caso de erro, desfaz todas as alterações
    mysqli_rollback($conn);
    error_log("Erro ao cancelar bilhete: " . $e->getMessage());
    header("Location: minhas_viagens.php?erro=sistema");
    exit();
}
?>

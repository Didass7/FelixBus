<?php
/**
 * Atualizar Perfil
 *
 * Este script processa a atualização dos dados do perfil do utilizador.
 * Recebe os dados do formulário, atualiza na base de dados e na sessão atual.
 */

// Iniciar a sessão para aceder aos dados do utilizador autenticado
session_start();

// inclui o ficheiro de conexão à base de dados
include '../basedados/basedados.h';

// verifica se o método é POST. (método HTTP para enviar dados de forma segura )
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // obtem o id do utilizador da sessão atual
    $id_utilizador = $_SESSION['id_utilizador'];

    // Obter e limpar os dados do formulário
    $nome_completo = trim($_POST['nome_completo']);
    $nome_utilizador = trim($_POST['nome_utilizador']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $morada = trim($_POST['morada']);

    // Preparar a query SQL para atualizar os dados do utilizador
    $sql = "UPDATE utilizadores SET
           nome_completo = ?,
           nome_utilizador = ?,
           email = ?,
           telefone = ?,
           morada = ?
           WHERE id_utilizador = ?";

    // Preparar a declaração SQL
    $stmt = mysqli_prepare($conn, $sql);

    // Associar os parâmetros à declaração preparada
    // 'sssssi' significa: string, string, string, string, string, integer
    mysqli_stmt_bind_param($stmt, "sssssi",
        $nome_completo,
        $nome_utilizador,
        $email,
        $telefone,
        $morada,
        $id_utilizador
    );

    // Executar a query e verificar se foi bem-sucedida
    if (mysqli_stmt_execute($stmt)) {
        // Atualizar os dados na sessão atual
        $_SESSION['nome_completo'] = $nome_completo;
        $_SESSION['nome_utilizador'] = $nome_utilizador;
        $_SESSION['email'] = $email;
        $_SESSION['telefone'] = $telefone;
        $_SESSION['morada'] = $morada;

        // Redirecionar para a página de perfil com mensagem de sucesso
        header("Location: perfil.php?success=1");
        exit();
    } else {
        // Mostrar mensagem de erro caso a atualização falhe
        echo "Erro ao atualizar o perfil: " . mysqli_error($conn);
    }

    // Fechar a declaração preparada
    mysqli_stmt_close($stmt);
}

// Fechar a conexão com a base de dados
mysqli_close($conn);
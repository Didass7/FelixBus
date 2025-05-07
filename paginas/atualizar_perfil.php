<?php
/**
 * Atualização de Perfil - FelixBus
 *
 * Este script processa a atualização dos dados do perfil do utilizador.
 * Recebe os dados do formulário, valida-os, atualiza na base de dados
 * e na sessão atual.
 *
 * @author FelixBus
 * @version 1.0
 */

// Iniciar sessão e verificar autenticação
session_start();
if (!isset($_SESSION['id_utilizador'])) {
    header("Location: login.php");
    exit();
}

// Verificar se o pedido é do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: perfil.php");
    exit();
}

// Incluir ligação à base de dados
include '../basedados/basedados.h';

// Obter dados do formulário
$id_utilizador = $_SESSION['id_utilizador'];
$nome_completo = trim($_POST['nome_completo']);
$nome_utilizador = trim($_POST['nome_utilizador']);
$email = trim($_POST['email']);
$telefone = trim($_POST['telefone']);
$morada = trim($_POST['morada']);

// Atualizar dados do utilizador na base de dados
$stmt = mysqli_prepare($conn,
    "UPDATE utilizadores SET
     nome_completo = ?, nome_utilizador = ?,
     email = ?, telefone = ?, morada = ?
     WHERE id_utilizador = ?"
);

mysqli_stmt_bind_param($stmt, "sssssi",
    $nome_completo, $nome_utilizador, $email,
    $telefone, $morada, $id_utilizador
);

// Processar resultado da atualização
if (mysqli_stmt_execute($stmt)) {
    // Atualizar dados na sessão
    $_SESSION['nome_completo'] = $nome_completo;
    $_SESSION['nome_utilizador'] = $nome_utilizador;
    $_SESSION['email'] = $email;
    $_SESSION['telefone'] = $telefone;
    $_SESSION['morada'] = $morada;

    header("Location: perfil.php?success=1");
} else {
    header("Location: perfil.php?error=1");
}
exit();
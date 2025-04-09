<?php
session_start();
include '../basedados/basedados.h';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilizador = $_SESSION['id_utilizador'];
    $nome_completo = trim($_POST['nome_completo']);
    $nome_utilizador = trim($_POST['nome_utilizador']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $morada = trim($_POST['morada']);

    $sql = "UPDATE utilizadores SET nome_completo = ?, nome_utilizador = ?, email = ?, telefone = ?, morada = ? WHERE id_utilizador = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $nome_completo, $nome_utilizador, $email, $telefone, $morada, $id_utilizador);

    if (mysqli_stmt_execute($stmt)) {
        // Atualizar os dados na sessão
        $_SESSION['nome_completo'] = $nome_completo;
        $_SESSION['nome_utilizador'] = $nome_utilizador;
        $_SESSION['email'] = $email;
        $_SESSION['telefone'] = $telefone;
        $_SESSION['morada'] = $morada;

        header("Location: perfil.php?success=1");
        exit();
    } else {
        echo "Erro ao atualizar o perfil: " . mysqli_error($conn);
    }
}
?>
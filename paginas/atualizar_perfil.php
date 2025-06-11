<?php


// iniciar sessão e verificar autenticação
session_start();
if (!isset($_SESSION['id_utilizador'])) {
    header("Location: login.php");
    exit();
}

// verificar se o pedido é do tipo post
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: perfil.php");
    exit();
}

// incluir ligação à base de dados
include '../basedados/basedados.h';

// obter dados do formulário
$id_utilizador = $_SESSION['id_utilizador'];
$nome_completo = trim($_POST['nome_completo']);
$nome_utilizador = trim($_POST['nome_utilizador']);
$email = trim($_POST['email']);
$telefone = trim($_POST['telefone']);
$morada = trim($_POST['morada']);

try {
    // atualizar dados do utilizador na base de dados
    $sql = "UPDATE utilizadores SET
            nome_completo = ?, nome_utilizador = ?,
            email = ?, telefone = ?, morada = ?
            WHERE id_utilizador = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Erro ao preparar a atualização");
    }

    $stmt->bind_param("sssssi",
        $nome_completo, $nome_utilizador, $email,
        $telefone, $morada, $id_utilizador
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar a atualização");
    }

    $stmt->close();

    // atualizar dados na sessão
    $_SESSION['nome_completo'] = $nome_completo;
    $_SESSION['nome_utilizador'] = $nome_utilizador;
    $_SESSION['email'] = $email;
    $_SESSION['telefone'] = $telefone;
    $_SESSION['morada'] = $morada;

    header("Location: perfil.php?success=1");
    exit();

} catch (Exception $e) {
    error_log("Erro ao atualizar perfil: " . $e->getMessage());
    header("Location: perfil.php?error=1");
    exit();
}
<?php
session_start();
require_once '../../basedados/basedados.h';

if (!isset($_SESSION['id_utilizador']) || $_SESSION['perfil'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $origem = $_POST['origem'];
    $destino = $_POST['destino'];
    $id_admin = $_SESSION['id_utilizador'];
    
    $sql = "INSERT INTO rotas (origem, destino, criado_por) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssi", $origem, $destino, $id_admin);
    mysqli_stmt_execute($stmt);
}
?>
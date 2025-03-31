<?php
require_once 'includes/conexao.php';
try {
    $pdo->query("SELECT 1"); // Consulta simples para testar a ligação
    echo "Ligação à base de dados bem-sucedida!";
} catch (PDOException $e) {
    echo "Falha na ligação: " . $e->getMessage();
}
?>
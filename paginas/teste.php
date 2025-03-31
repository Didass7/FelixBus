<?php
// paginas/TesteConexao.php
require_once '../basedados/basedados.h'; // Inclui o módulo de conexão

echo "<h2>A testar ligação à base de dados...</h2>";

// Tentar estabelecer ligação
$conn = conectarBD();

if ($conn) {
    echo "<p style='color: green;'>Ligação à base de dados bem-sucedida!</p>";

    // Testar consulta simples (ex: obter número de utilizadores)
    $query = "SELECT COUNT(*) AS total FROM utilizadores";
    $resultado = mysqli_query($conn, $query);

    if ($resultado) {
        $dados = mysqli_fetch_assoc($resultado);
        echo "<p>Utilizadores registados: " . $dados['total'] . "</p>";
    } else {
        echo "<p style='color: red;'>Erro na consulta: " . mysqli_error($conn) . "</p>";
    }

    mysqli_close($conn); // Fechar ligação
} else {
    echo "<p style='color: red;'>Falha na ligação. Verifique:</p>";
    echo "<ul>";
    echo "<li>Servidor MySQL está em execução?</li>";
    echo "<li>Credenciais em <code>basedados.h</code> estão corretas?</li>";
    echo "<li>A base de dados <code>felixbus</code> existe?</li>";
    echo "</ul>";
}
?>
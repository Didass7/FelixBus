<?php
// basedados/basedados.h
    $host = 'localhost';
    $user = 'root';       // Utilizador da BD
    $password = '';       // Password (se aplicável)
    $database = 'felixbus';

    $conn = mysqli_connect($host, $user, $password, $database);

    if (!$conn) {
        die("Erro de ligação: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8mb4");
?>
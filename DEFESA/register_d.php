<?php 

session_start();
include '../basedados/basedados.h';

//VERIFICAÇÃO DE SESSÃO

if(isset($_SESSION['id_utilizador'])) {
    switch ($_SESSION['perfil']){
        case 'administrador':
            header("Location: pagina_inicial_admin.php");
            break;
        case 'funcionário':
            header("Location: pagina_inicial_cliente.php");
            break;
        case 'cliente':
            header("Location: pagina_inicial_cliente.php");
            break;
        default:
            header("Location: login.php");
    }
    exit;
}

?>
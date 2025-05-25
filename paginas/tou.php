session_start();
include '../basedados/basedados.h';

if( !isset($_SESSION['id_utilizador'])|| $_SESSION['perfil']!= 'cliente'){
    header("Location: login.php");
    exit();
}
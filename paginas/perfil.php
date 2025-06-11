<?php
session_start(); // inicia a sessão

include '../basedados/basedados.h'; // inclui a ligação à base de dados

// verifica se o utilizador está autenticado com perfil válido
if (!isset($_SESSION['id_utilizador']) || !in_array($_SESSION['perfil'], ['cliente', 'funcionário', 'administrador'])) {
    header("Location: login.php");
    exit();
}

$valor_padrao = 'Não disponível'; // valor padrão para campos vazios
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Perfil do Utilizador</title>
    <link rel="stylesheet" href="perfil.css">
</head>
<body>
    <!-- barra de navegação com links dinâmicos baseados no perfil -->
    <nav class="navbar">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>

        <div class="nav-links">
            <?php
            switch ($_SESSION['perfil']) {
                case 'cliente':
                    echo '<a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>';
                    echo '<a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>';
                    echo '<a href="carteira.php" class="nav-link">Carteira</a>';
                    break;
                case 'funcionário':
                    echo '<a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>';
                    break;
                case 'administrador':
                    echo '<a href="pagina_inicial_admin.php" class="nav-link">Painel</a>';
                    break;
            }

            echo '<a href="perfil.php" class="nav-link">Perfil</a>';
            echo '<a href="logout.php" class="nav-link">Sair</a>';
            ?>
        </div>
    </nav>

    <!-- secção de perfil do utilizador com informações e formulário de edição -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">
                Bem-Vindo ao seu perfil,
                <span class="user-name">
                    <?php echo isset($_SESSION['nome_completo']) && !empty($_SESSION['nome_completo']) ? htmlspecialchars($_SESSION['nome_completo']) : ''; ?>
                </span>
            </h1>

            <section class="user-info">
                <div class="user-info-container">
                    <h2>Informações do Utilizador</h2>

                    <ul id="user-info-display">
                        <?php
                        $campos = [
                            'nome_completo' => 'Nome Completo',
                            'nome_utilizador' => 'Nome de Utilizador',
                            'email' => 'Email',
                            'telefone' => 'Telefone',
                            'morada' => 'Morada'
                        ];

                        foreach ($campos as $campo => $label) {
                            $valor = isset($_SESSION[$campo]) && !empty($_SESSION[$campo]) ?
                                    $_SESSION[$campo] : $valor_padrao;
                            echo '<li><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($valor) . '</li>';
                        }
                        ?>
                    </ul>

                    <button id="edit-button" class="btn-primary">Editar</button>

                    <form method="POST" action="atualizar_perfil.php" id="edit-form" style="display: none;">
                        <ul>
                            <?php
                            foreach ($campos as $campo => $label) {
                                echo '<li>';
                                echo '<label for="' . htmlspecialchars($campo) . '">' . htmlspecialchars($label) . ':</label>';

                                $tipo = ($campo == 'email') ? 'email' : 'text';
                                $valor = isset($_SESSION[$campo]) && !empty($_SESSION[$campo]) ?
                                        $_SESSION[$campo] : '';

                                echo '<input type="' . htmlspecialchars($tipo) . '" name="' . htmlspecialchars($campo) . '" id="' . htmlspecialchars($campo) . '" ';
                                echo 'value="' . htmlspecialchars($valor) . '">';
                                echo '</li>';
                            }
                            ?>
                        </ul>

                        <div class="button-group">
                            <button type="submit" class="btn-primary">Guardar</button>
                            <button type="button" id="cancel-button" class="btn-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </section>

    <!-- script para alternar entre visualização e edição -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('edit-button');
            const cancelBtn = document.getElementById('cancel-button');
            const form = document.getElementById('edit-form');
            const info = document.getElementById('user-info-display');

            editBtn.addEventListener('click', () => {
                info.style.display = 'none';
                form.style.display = 'block';
                editBtn.style.display = 'none';
            });

            cancelBtn.addEventListener('click', () => {
                form.style.display = 'none';
                info.style.display = 'block';
                editBtn.style.display = 'block';
            });
        });
    </script>

    <!-- rodapé com links úteis e redes sociais -->
    <footer class="footer">
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>

        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <p>&copy; <?php echo date('Y'); ?> FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

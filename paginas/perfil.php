<?php
// Inicia a sessão
session_start();

// Inclui ligação à base de dados
include '../basedados/basedados.h';

// Verifica se o utilizador está autenticado com perfil válido
if (!isset($_SESSION['id_utilizador']) || !in_array($_SESSION['perfil'], ['cliente', 'funcionário', 'administrador'])) {
    header("Location: login.php");
    exit();
}

// Valor padrão para campos vazios
$valor_padrao = 'Não disponível';
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
    <!-- Barra de Navegação -->
    <nav class="navbar">
        <div class="logo">
            <?php
            // Define página inicial conforme perfil
            $pagina_inicial = 'index.php';
            if ($_SESSION['perfil'] == 'cliente') {
                $pagina_inicial = 'pagina_inicial_cliente.php';
            } elseif ($_SESSION['perfil'] == 'funcionário') {
                $pagina_inicial = 'pagina_inicial_funcionario.php';
            } elseif ($_SESSION['perfil'] == 'administrador') {
                $pagina_inicial = 'pagina_inicial_admin.php';
            }
            ?>
            <a href="<?php echo $pagina_inicial; ?>">
                <img src="logo.png" alt="FelixBus Logo">
            </a>
        </div>

        <div class="nav-links">
            <?php
            // Links de navegação
            if ($_SESSION['perfil'] == 'cliente') {
                echo '<a href="consultar_rotas.php" class="nav-link">Rotas e Horários</a>';
                echo '<a href="minhas_viagens.php" class="nav-link">Minhas Viagens</a>';
                echo '<a href="carteira.php" class="nav-link">Carteira</a>';
            } elseif ($_SESSION['perfil'] == 'funcionário') {
                echo '<a href="pagina_inicial_funcionario.php" class="nav-link">Área do Funcionário</a>';
            } elseif ($_SESSION['perfil'] == 'administrador') {
                echo '<a href="pagina_inicial_admin.php" class="nav-link">Painel</a>';
            }

            // Links comuns a todos os perfis
            echo '<a href="perfil.php" class="nav-link">Perfil</a>';
            echo '<a href="logout.php" class="nav-link">Sair</a>';
            ?>
        </div>
    </nav>

    <!-- Perfil do Utilizador -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">
                Bem-Vindo ao seu perfil,
                <span class="user-name">
                    <?php echo isset($_SESSION['nome_completo']) && !empty($_SESSION['nome_completo']) ? $_SESSION['nome_completo'] : ''; ?>
                </span>
            </h1>

            <!-- Informações do Utilizador -->
            <section class="user-info">
                <div class="user-info-container">
                    <h2>Informações do Utilizador</h2>

                    <!-- Dados do utilizador -->
                    <ul id="user-info-display">
                        <?php
                        // Campos a mostrar
                        $campos = [
                            'nome_completo' => 'Nome Completo',
                            'nome_utilizador' => 'Nome de Utilizador',
                            'email' => 'Email',
                            'telefone' => 'Telefone',
                            'morada' => 'Morada'
                        ];

                        // Mostra cada campo
                        foreach ($campos as $campo => $label) {
                            $valor = isset($_SESSION[$campo]) && !empty($_SESSION[$campo]) ?
                                    $_SESSION[$campo] : $valor_padrao;
                            echo '<li><strong>' . $label . ':</strong> ' . $valor . '</li>';
                        }
                        ?>
                    </ul>

                    <!-- Botão de edição -->
                    <button id="edit-button" class="btn-primary">Editar</button>

                    <!-- Formulário de edição (oculto) -->
                    <form method="POST" action="atualizar_perfil.php" id="edit-form" style="display: none;">
                        <ul>
                            <?php
                            // Gera campos do formulário
                            foreach ($campos as $campo => $label) {
                                echo '<li>';
                                echo '<label for="' . $campo . '">' . $label . ':</label>';

                                // Tipo de campo
                                $tipo = ($campo == 'email') ? 'email' : 'text';

                                // Valor do campo
                                $valor = isset($_SESSION[$campo]) && !empty($_SESSION[$campo]) ?
                                        $_SESSION[$campo] : '';

                                echo '<input type="' . $tipo . '" name="' . $campo . '" id="' . $campo . '" ';
                                echo 'value="' . $valor . '">';
                                echo '</li>';
                            }
                            ?>
                        </ul>

                        <!-- Botões -->
                        <div class="button-group">
                            <button type="submit" class="btn-primary">Guardar</button>
                            <button type="button" id="cancel-button" class="btn-secondary">Cancelar</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </section>

    <!-- Script para mostrar/ocultar formulário -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos da página
            const editBtn = document.getElementById('edit-button');
            const cancelBtn = document.getElementById('cancel-button');
            const form = document.getElementById('edit-form');
            const info = document.getElementById('user-info-display');

            // Botão Editar
            editBtn.addEventListener('click', () => {
                info.style.display = 'none';
                form.style.display = 'block';
                editBtn.style.display = 'none';
            });

            // Botão Cancelar
            cancelBtn.addEventListener('click', () => {
                form.style.display = 'none';
                info.style.display = 'block';
                editBtn.style.display = 'block';
            });
        });
    </script>

    <!-- Rodapé -->
    <footer class="footer">
        <!-- Redes sociais -->
        <div class="social-links">
            <a href="#" class="social-link">FB</a>
            <a href="#" class="social-link">TW</a>
            <a href="#" class="social-link">IG</a>
        </div>

        <!-- Links úteis -->
        <div class="footer-links">
            <a href="empresa.php" class="footer-link">Sobre Nós</a>
            <a href="empresa.php#contactos" class="footer-link">Contactos</a>
            <a href="consultar_rotas.php" class="footer-link">Rotas e Horários</a>
        </div>

        <!-- Copyright -->
        <p>&copy; <?php echo date('Y'); ?> FelixBus. Todos os direitos reservados.</p>
    </footer>
</body>
</html>

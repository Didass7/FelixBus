-- Criar base de dados
DROP DATABASE IF EXISTS felixbus;
CREATE DATABASE felixbus CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE felixbus;

-- Criar tabelas

-- Tabela de Utilizadores
CREATE TABLE utilizadores (
    id_utilizador INT AUTO_INCREMENT PRIMARY KEY,
    nome_utilizador VARCHAR(255) UNIQUE NOT NULL COMMENT 'Nome único para login',
    hash_password VARCHAR(255) NOT NULL COMMENT 'Password encriptada',
    email VARCHAR(255) UNIQUE NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    morada TEXT,
    perfil ENUM('cliente', 'funcionário', 'administrador') NOT NULL DEFAULT 'cliente',
    data_registo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN NOT NULL DEFAULT 1 COMMENT 'Conta ativa ou desativada'
);

-- Tabela de Rotas
CREATE TABLE rotas (
    id_rota INT AUTO_INCREMENT PRIMARY KEY,
    origem VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    criado_por INT NOT NULL COMMENT 'Administrador que criou a rota',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES utilizadores(id_utilizador)
);

-- Tabela de Horários
CREATE TABLE horarios (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_rota INT NOT NULL,
    hora_partida DATETIME NOT NULL,
    hora_chegada DATETIME NOT NULL,
    capacidade_autocarro INT NOT NULL COMMENT 'Número total de lugares',
    lugares_disponiveis INT NOT NULL COMMENT 'Lugares restantes',
    preco DECIMAL(10,2) NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rota) REFERENCES rotas(id_rota)
);

-- Tabela de Alertas/Promoções
CREATE TABLE alertas (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    criado_por INT NOT NULL COMMENT 'Administrador responsável',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN NOT NULL DEFAULT 1 COMMENT 'Se o alerta está visível',
    FOREIGN KEY (criado_por) REFERENCES utilizadores(id_utilizador)
);

-- Tabela de Carteiras
CREATE TABLE carteiras (
    id_carteira INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT UNIQUE COMMENT 'Null para carteira da empresa',
    tipo ENUM('cliente', 'empresa') NOT NULL DEFAULT 'cliente',
    saldo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizador) REFERENCES utilizadores(id_utilizador) ON DELETE CASCADE
);

-- Carteira da Empresa (inserção inicial)
INSERT INTO carteiras (id_utilizador, tipo, saldo) VALUES (NULL, 'empresa', 0.00);

-- Tabela de Transações
CREATE TABLE transacoes (
    id_transacao INT AUTO_INCREMENT PRIMARY KEY,
    id_carteira_origem INT COMMENT 'Null para depósitos iniciais',
    id_carteira_destino INT COMMENT 'Null para levantamentos',
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('deposito', 'levantamento', 'transferencia') NOT NULL,
    descricao TEXT COMMENT 'Detalhes da operação',
    data_operacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_carteira_origem) REFERENCES carteiras(id_carteira),
    FOREIGN KEY (id_carteira_destino) REFERENCES carteiras(id_carteira)
);

-- Tabela de Bilhetes
CREATE TABLE bilhetes (
    codigo_bilhete CHAR(36) PRIMARY KEY DEFAULT (UUID()) COMMENT 'Código único de validação',
    id_horario INT NOT NULL,
    id_utilizador INT NOT NULL,
    data_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    preco_pago DECIMAL(10,2) NOT NULL COMMENT 'Valor no momento da compra',
    valido BOOLEAN NOT NULL DEFAULT 1 COMMENT 'Se o bilhete está ativo',
    FOREIGN KEY (id_horario) REFERENCES horarios(id_horario),
    FOREIGN KEY (id_utilizador) REFERENCES utilizadores(id_utilizador)
);

--Inserir utilizadores
INSERT INTO utilizadores (nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil) VALUES
('cliente', md5('cliente'), 'cliente@gmail.com', 'cliente', '999888777', 'Rua 1', 'cliente');

INSERT INTO utilizadores 
(nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil) VALUES
 ('funcionario', MD5('funcionario'), 'funcionario@gmail.com', 'Funcionário', '999888777', 'Rua 2', 'funcionário');

 INSERT INTO utilizadores 
(nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil) VALUES 
('admin', MD5('admin'), 'admin@gmail.com', 'Administrador', '933333333', 'Rua 3', 'administrador');

-Inserir rotas
INSERT INTO rotas (origem, destino, criado_por) VALUES
('Glasgow', 'Londres', 7),
('Santiago de Compostela', 'Bratislava', 7),
('Chișinău', 'Tirana', 7),
('Budapeste', 'Madrid', 7),
('Vilnius', 'Estrasburgo', 7),
('Genebra', 'Estocolmo', 7),
('Berlim', 'Malmo', 7),
('Florença', 'Tampere', 7),
('Estugarda', 'Bratislava', 7),
('Cork', 'Santiago de Compostela', 7),
('Bilbau', 'Florença', 7),
('Split', 'Santiago de Compostela', 7),
('Andorra-a-Velha', 'Cidade do Vaticano', 7),
('Debrecen', 'Atenas', 7),
('Wroclaw', 'Varsóvia', 7),
('Valência', 'Luxemburgo', 7),
('Podgorica', 'Londres', 7),
('Malmo', 'Moscovo', 7),
('Varsóvia', 'Debrecen', 7),
('Glasgow', 'Estugarda', 7),
('Reykjavik', 'São Marinho', 7),
('Palermo', 'Genebra', 7),
('Genebra', 'Kiev', 7),
('Copenhaga', 'Gotemburgo', 7),
('Liubliana', 'Tallinn', 7),
('Viena', 'Amesterdão', 7),
('Cracóvia', 'Podgorica', 7),
('Sevilha', 'Sófia', 7),
('Riga', 'Santiago de Compostela', 7),
('Palermo', 'Nice', 7),
('Chișinău', 'Hanover', 7),
('Málaga', 'Skopje', 7),
('Nantes', 'Amesterdão', 7),
('Oslo', 'Toulouse', 7),
('Oslo', 'Toulouse', 7),
('Dublin', 'Ghent', 7),
('Génova', 'Milão', 7),
('Dublin', 'Bergen', 7),
('Bergen', 'Roterdão', 7),
('Aarhus', 'Bergen', 7),
('Estugarda', 'Podgorica', 7),
('Tirana', 'Edimburgo', 7),
('São Marinho', 'Malmo', 7),
('Munique', 'Londres', 7),
('Glasgow', 'Frankfurt', 7),
('São Marinho', 'Amesterdão', 7),
('Roterdão', 'Manchester', 7),
('Berlim', 'Estugarda', 7),
('Varsóvia', 'Bratislava', 7),
('Roma', 'Ghent', 7);

--Inserir horários
INSERT INTO horarios (id_rota, hora_partida, hora_chegada, capacidade_autocarro, lugares_disponiveis, preco) VALUES
(50, '2025-04-10 15:00:00', '2025-04-11 00:00:00', 50, 50, 115.33),
(49, '2025-04-10 21:00:00', '2025-04-10 23:00:00', 50, 50, 65.40),
(48, '2025-04-10 09:00:00', '2025-04-10 15:00:00', 50, 50, 58.44),
(47, '2025-04-10 11:00:00', '2025-04-10 15:00:00', 50, 50, 79.61),
(46, '2025-04-10 09:00:00', '2025-04-10 11:00:00', 50, 50, 91.01),
(45, '2025-04-10 13:00:00', '2025-04-10 17:00:00', 50, 50, 101.43),
(44, '2025-04-10 11:00:00', '2025-04-10 15:00:00', 50, 50, 77.32),
(43, '2025-04-10 06:00:00', '2025-04-10 10:00:00', 50, 50, 66.55),
(42, '2025-04-10 15:00:00', '2025-04-10 20:00:00', 50, 50, 76.62),
(41, '2025-04-10 15:00:00', '2025-04-10 17:00:00', 50, 50, 72.66),
(40, '2025-04-10 16:00:00', '2025-04-10 23:00:00', 50, 50, 34.34),
(39, '2025-04-10 08:00:00', '2025-04-10 11:00:00', 50, 50, 45.08),
(38, '2025-04-10 07:00:00', '2025-04-10 13:00:00', 50, 50, 68.97),
(37, '2025-04-10 21:00:00', '2025-04-11 00:00:00', 50, 50, 40.84),
(36, '2025-04-10 17:00:00', '2025-04-10 23:00:00', 50, 50, 114.80),
(35, '2025-04-10 21:00:00', '2025-04-11 06:00:00', 50, 50, 81.53),
(34, '2025-04-10 14:00:00', '2025-04-10 22:00:00', 50, 50, 43.05),
(33, '2025-04-10 08:00:00', '2025-04-10 16:00:00', 50, 50, 62.48),
(32, '2025-04-10 06:00:00', '2025-04-10 14:00:00', 50, 50, 16.20),
(31, '2025-04-10 17:00:00', '2025-04-10 23:00:00', 50, 50, 78.97),
(30, '2025-04-10 12:00:00', '2025-04-10 15:00:00', 50, 50, 80.42),
(29, '2025-04-10 16:00:00', '2025-04-10 21:00:00', 50, 50, 40.96),
(28, '2025-04-10 06:00:00', '2025-04-10 10:00:00', 50, 50, 35.70),
(27, '2025-04-10 12:00:00', '2025-04-10 16:00:00', 50, 50, 20.26),
(26, '2025-04-10 17:00:00', '2025-04-10 21:00:00', 50, 50, 26.43),
(25, '2025-04-10 21:00:00', '2025-04-11 02:00:00', 50, 50, 57.66),
(24, '2025-04-10 17:00:00', '2025-04-10 21:00:00', 50, 50, 79.32),
(23, '2025-04-10 06:00:00', '2025-04-10 11:00:00', 50, 50, 88.87),
(22, '2025-04-10 13:00:00', '2025-04-10 16:00:00', 50, 50, 47.98),
(21, '2025-04-10 10:00:00', '2025-04-10 15:00:00', 50, 50, 38.98),
(20, '2025-04-10 06:00:00', '2025-04-10 10:00:00', 50, 50, 92.88),
(19, '2025-04-10 16:00:00', '2025-04-10 18:00:00', 50, 50, 27.78),
(18, '2025-04-10 17:00:00', '2025-04-10 20:00:00', 50, 50, 74.79),
(17, '2025-04-10 13:00:00', '2025-04-10 20:00:00', 50, 50, 92.92),
(16, '2025-04-10 19:00:00', '2025-04-11 04:00:00', 50, 50, 54.38),
(15, '2025-04-10 06:00:00', '2025-04-10 08:00:00', 50, 50, 111.17),
(14, '2025-04-10 15:00:00', '2025-04-10 17:00:00', 50, 50, 85.39),
(13, '2025-04-10 09:00:00', '2025-04-10 18:00:00', 50, 50, 119.73),
(12, '2025-04-10 09:00:00', '2025-04-10 12:00:00', 50, 50, 42.54),
(11, '2025-04-10 19:00:00', '2025-04-11 00:00:00', 50, 50, 90.21),
(10, '2025-04-10 09:00:00', '2025-04-10 11:00:00', 50, 50, 48.06),
(9, '2025-04-10 17:00:00', '2025-04-10 22:00:00', 50, 50, 119.54),
(8, '2025-04-10 17:00:00', '2025-04-11 00:00:00', 50, 50, 45.11),
(7, '2025-04-10 13:00:00', '2025-04-10 18:00:00', 50, 50, 81.09),
(6, '2025-04-10 21:00:00', '2025-04-10 23:00:00', 50, 50, 19.36),
(5, '2025-04-10 12:00:00', '2025-04-10 19:00:00', 50, 50, 49.38),
(4, '2025-04-10 16:00:00', '2025-04-10 20:00:00', 50, 50, 47.63),
(3, '2025-04-10 20:00:00', '2025-04-11 01:00:00', 50, 50, 87.38),
(2, '2025-04-10 07:00:00', '2025-04-10 11:00:00', 50, 50, 14.55),
(1, '2025-04-10 13:00:00', '2025-04-10 16:00:00', 50, 50, 44.85);

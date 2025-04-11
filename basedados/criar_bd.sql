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

-- Primeiro inserir as rotas usando o admin correto (id=7)
INSERT INTO rotas (id_rota, origem, destino, criado_por) VALUES
(1, 'Lisboa', 'Porto', 7),
(2, 'Porto', 'Lisboa', 7),
(3, 'Lisboa', 'Coimbra', 7),
(4, 'Coimbra', 'Lisboa', 7),
(5, 'Porto', 'Braga', 7),
(6, 'Braga', 'Porto', 7),
(7, 'Coimbra', 'Porto', 7),
(8, 'Porto', 'Coimbra', 7),
(9, 'Lisboa', 'Faro', 7),
(10, 'Faro', 'Lisboa', 7),
(11, 'Coimbra', 'Braga', 7),
(12, 'Braga', 'Coimbra', 7),
(13, 'Lisboa', 'Madrid', 7),
(14, 'Madrid', 'Lisboa', 7),
(15, 'Porto', 'Madrid', 7),
(16, 'Madrid', 'Porto', 7),
(17, 'Madrid', 'Barcelona', 7),
(18, 'Barcelona', 'Madrid', 7),
(19, 'Lisboa', 'Barcelona', 7),
(20, 'Barcelona', 'Lisboa', 7),
(21, 'Madrid', 'Paris', 7),
(22, 'Paris', 'Madrid', 7),
(23, 'Lisboa', 'Paris', 7),
(24, 'Paris', 'Lisboa', 7),
(25, 'Paris', 'Bruxelas', 7),
(26, 'Bruxelas', 'Paris', 7),
(27, 'Lisboa', 'Bruxelas', 7),
(28, 'Bruxelas', 'Lisboa', 7),
(29, 'Porto', 'Paris', 7),
(30, 'Paris', 'Porto', 7),
(31, 'Lisboa', 'Londres', 7),
(32, 'Londres', 'Lisboa', 7),
(33, 'Porto', 'Londres', 7),
(34, 'Londres', 'Porto', 7),
(35, 'Madrid', 'Londres', 7),
(36, 'Londres', 'Madrid', 7),
(37, 'Barcelona', 'Londres', 7),
(38, 'Londres', 'Barcelona', 7),
(39, 'Paris', 'Londres', 7),
(40, 'Londres', 'Paris', 7),
(41, 'Madrid', 'Bruxelas', 7),
(42, 'Bruxelas', 'Madrid', 7),
(43, 'Barcelona', 'Paris', 7),
(44, 'Paris', 'Barcelona', 7),
(45, 'Porto', 'Bruxelas', 7),
(46, 'Bruxelas', 'Porto', 7),
(47, 'Faro', 'Madrid', 7),
(48, 'Madrid', 'Faro', 7),
(49, 'Coimbra', 'Madrid', 7),
(50, 'Madrid', 'Coimbra', 7);


-- Garantir que o AUTO_INCREMENT está correto
ALTER TABLE rotas AUTO_INCREMENT = 51;

-- Inserir horários para as rotas
INSERT INTO horarios (id_rota, hora_partida, hora_chegada, capacidade_autocarro, lugares_disponiveis, preco) VALUES
(1, '2025-04-10 08:00:00', '2025-04-10 12:30:00', 50, 50, 60.00),
(2, '2025-04-10 09:00:00', '2025-04-10 15:30:00', 50, 50, 75.00),
(3, '2025-04-10 10:00:00', '2025-04-10 17:00:00', 50, 50, 85.00),
(4, '2025-04-10 07:00:00', '2025-04-10 18:00:00', 50, 50, 130.00),
(5, '2025-04-10 06:00:00', '2025-04-10 19:30:00', 50, 50, 140.00),
(6, '2025-04-10 11:00:00', '2025-04-10 18:00:00', 50, 50, 85.00),
(7, '2025-04-10 12:00:00', '2025-04-10 18:30:00', 50, 50, 75.00),
(8, '2025-04-10 13:00:00', '2025-04-10 18:00:00', 50, 50, 65.00),
(9, '2025-04-10 14:00:00', '2025-04-10 19:30:00', 50, 50, 70.00),
(10, '2025-04-10 15:00:00', '2025-04-10 21:00:00', 50, 50, 80.00),
(11, '2025-04-10 16:00:00', '2025-04-10 22:30:00', 50, 50, 85.00),
(12, '2025-04-10 17:00:00', '2025-04-11 00:00:00', 50, 50, 90.00),
(13, '2025-04-10 18:00:00', '2025-04-11 01:30:00', 50, 50, 95.00),
(14, '2025-04-10 19:00:00', '2025-04-11 02:00:00', 50, 50, 95.00),
(15, '2025-04-10 11:00:00', '2025-04-10 12:45:00', 50, 50, 30.00),
(16, '2025-04-10 12:00:00', '2025-04-10 13:45:00', 50, 50, 30.00),
(17, '2025-04-10 13:00:00', '2025-04-10 15:00:00', 50, 50, 35.00),
(18, '2025-04-10 14:00:00', '2025-04-10 16:30:00', 50, 50, 40.00),
(19, '2025-04-10 15:00:00', '2025-04-10 17:30:00', 50, 50, 45.00),
(20, '2025-04-10 14:00:00', '2025-04-10 18:00:00', 50, 50, 55.00),
(21, '2025-04-10 15:00:00', '2025-04-10 19:00:00', 50, 50, 60.00),
(22, '2025-04-10 16:00:00', '2025-04-10 20:00:00', 50, 50, 65.00),
(23, '2025-04-10 17:00:00', '2025-04-10 21:00:00', 50, 50, 70.00),
(24, '2025-04-10 18:00:00', '2025-04-10 22:00:00', 50, 50, 75.00),
(25, '2025-04-10 16:00:00', '2025-04-10 18:30:00', 50, 50, 40.00),
(26, '2025-04-10 17:00:00', '2025-04-10 19:30:00', 50, 50, 45.00),
(27, '2025-04-10 18:00:00', '2025-04-10 20:30:00', 50, 50, 50.00),
(28, '2025-04-10 19:00:00', '2025-04-10 21:30:00', 50, 50, 55.00),
(29, '2025-04-10 20:00:00', '2025-04-10 22:30:00', 50, 50, 60.00),
(30, '2025-04-10 12:00:00', '2025-04-10 15:00:00', 50, 50, 45.00),
(31, '2025-04-10 13:00:00', '2025-04-10 16:00:00', 50, 50, 50.00),
(32, '2025-04-10 14:00:00', '2025-04-10 17:00:00', 50, 50, 55.00),
(33, '2025-04-10 15:00:00', '2025-04-10 18:00:00', 50, 50, 60.00),
(34, '2025-04-10 16:00:00', '2025-04-10 19:00:00', 50, 50, 65.00),
(35, '2025-04-10 13:00:00', '2025-04-10 14:30:00', 50, 50, 25.00),
(36, '2025-04-10 14:00:00', '2025-04-10 15:30:00', 50, 50, 30.00),
(37, '2025-04-10 15:00:00', '2025-04-10 16:30:00', 50, 50, 35.00),
(38, '2025-04-10 16:00:00', '2025-04-10 17:30:00', 50, 50, 40.00),
(39, '2025-04-10 17:00:00', '2025-04-10 18:30:00', 50, 50, 45.00),
(40, '2025-04-10 10:30:00', '2025-04-10 13:30:00', 50, 50, 40.00),
(41, '2025-04-10 11:30:00', '2025-04-10 14:30:00', 50, 50, 45.00),
(42, '2025-04-10 12:30:00', '2025-04-10 15:30:00', 50, 50, 50.00),
(43, '2025-04-10 13:30:00', '2025-04-10 16:30:00', 50, 50, 55.00),
(44, '2025-04-10 14:30:00', '2025-04-10 17:30:00', 50, 50, 60.00),
(45, '2025-04-10 09:30:00', '2025-04-10 11:30:00', 50, 50, 30.00),
(46, '2025-04-10 10:30:00', '2025-04-10 12:30:00', 50, 50, 35.00),
(47, '2025-04-10 11:30:00', '2025-04-10 13:30:00', 50, 50, 40.00),
(48, '2025-04-10 12:30:00', '2025-04-10 14:30:00', 50, 50, 45.00),
(49, '2025-04-10 13:30:00', '2025-04-10 15:30:00', 50, 50, 50.00),
(50, '2025-04-10 15:00:00', '2025-04-10 22:00:00', 50, 50, 90.00);

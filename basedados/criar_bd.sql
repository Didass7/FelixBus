-- Criar base de dados
DROP DATABASE IF EXISTS felixbus;
CREATE DATABASE felixbus CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE felixbus;

-- Criar tabelas

-- Tabela de Utilizadores
CREATE TABLE utilizadores (
    id_utilizador INT AUTO_INCREMENT PRIMARY KEY,
    nome_utilizador VARCHAR(255) UNIQUE NOT NULL
    hash_password VARCHAR(255) NOT NULL
    email VARCHAR(255) UNIQUE NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    morada TEXT,
    perfil ENUM('cliente', 'funcionário', 'administrador') NOT NULL DEFAULT 'cliente',
    data_registo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validado BOOLEAN NOT NULL DEFAULT 0
);

-- Tabela de Rotas
CREATE TABLE rotas (
    id_rota INT AUTO_INCREMENT PRIMARY KEY,
    origem VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    criado_por INT NOT NULL
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES utilizadores(id_utilizador)
);

-- Tabela de Horários
CREATE TABLE horarios (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_rota INT NOT NULL,
    hora_partida TIME NOT NULL,
    hora_chegada TIME NOT NULL,
    capacidade_autocarro INT NOT NULL,
    lugares_disponiveis INT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NULL,
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
    criado_por INT NOT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN NOT NULL DEFAULT 1,
    FOREIGN KEY (criado_por) REFERENCES utilizadores(id_utilizador)
);

-- Tabela de Carteiras
CREATE TABLE carteiras (
    id_carteira INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizador INT UNIQUE,
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
    id_carteira_origem INT,
    id_carteira_destino INT,
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('deposito', 'levantamento', 'transferencia') NOT NULL,
    descricao TEXT,
    data_operacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_carteira_origem) REFERENCES carteiras(id_carteira),
    FOREIGN KEY (id_carteira_destino) REFERENCES carteiras(id_carteira)
);

-- Tabela de Bilhetes
CREATE TABLE bilhetes (
    codigo_bilhete CHAR(8) PRIMARY KEY,
    id_horario INT NOT NULL,
    id_utilizador INT NOT NULL,
    data_viagem DATE NOT NULL,
    data_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    preco_pago DECIMAL(10,2) NOT NULL,
    valido BOOLEAN NOT NULL DEFAULT 1,
    numero_lugar INT NOT NULL,
    comprado_por INT,
    FOREIGN KEY (id_horario) REFERENCES horarios(id_horario),
    FOREIGN KEY (id_utilizador) REFERENCES utilizadores(id_utilizador),
    FOREIGN KEY (comprado_por) REFERENCES utilizadores(id_utilizador)
);

-- Tabela para gerenciar capacidade das viagens por data
CREATE TABLE viagens_diarias (
    id_viagem_diaria INT AUTO_INCREMENT PRIMARY KEY,
    id_horario INT NOT NULL,
    data_viagem DATE NOT NULL,
    lugares_disponiveis INT NOT NULL,
    FOREIGN KEY (id_horario) REFERENCES horarios(id_horario),
    UNIQUE KEY viagem_data_unica (id_horario, data_viagem)
);

-- Inserir utilizadores
INSERT INTO utilizadores (nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil, validado) VALUES
('cliente', md5('cliente'), 'cliente@gmail.com', 'cliente', '999888777', 'Rua 1', 'cliente', 1);

INSERT INTO utilizadores 
(nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil, validado) VALUES
('funcionario', MD5('funcionario'), 'funcionario@gmail.com', 'Funcionário', '999888777', 'Rua 2', 'funcionário', 1);

INSERT INTO utilizadores 
(nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil, validado) VALUES 
('admin', MD5('admin'), 'admin@gmail.com', 'Administrador', '933333333', 'Rua 3', 'administrador', 1);

-- Primeiro inserir as rotas
INSERT INTO rotas (id_rota, origem, destino, criado_por) VALUES 
(1, 'Lisboa', 'Porto', 3),
(2, 'Porto', 'Lisboa', 3),
(3, 'Lisboa', 'Coimbra', 3),
(4, 'Coimbra', 'Lisboa', 3),
(5, 'Porto', 'Braga', 3),
(6, 'Braga', 'Porto', 3),
(7, 'Coimbra', 'Porto', 3),
(8, 'Porto', 'Coimbra', 3),
(9, 'Lisboa', 'Faro', 3),
(10, 'Faro', 'Lisboa', 3),
(11, 'Coimbra', 'Braga', 3),
(12, 'Braga', 'Coimbra', 3),
(13, 'Lisboa', 'Madrid', 3),
(14, 'Madrid', 'Lisboa', 3),
(15, 'Porto', 'Madrid', 3),
(16, 'Madrid', 'Porto', 3),
(17, 'Madrid', 'Barcelona', 3),
(18, 'Barcelona', 'Madrid', 3),
(19, 'Lisboa', 'Barcelona', 3),
(20, 'Barcelona', 'Lisboa', 3),
(21, 'Madrid', 'Paris', 3),
(22, 'Paris', 'Madrid', 3),
(23, 'Lisboa', 'Paris', 3),
(24, 'Paris', 'Lisboa', 3),
(25, 'Paris', 'Bruxelas', 3),
(26, 'Bruxelas', 'Paris', 3),
(27, 'Lisboa', 'Bruxelas', 3),
(28, 'Bruxelas', 'Lisboa', 3),
(29, 'Porto', 'Paris', 3),
(30, 'Paris', 'Porto', 3),
(31, 'Lisboa', 'Londres', 3),
(32, 'Londres', 'Lisboa', 3),
(33, 'Porto', 'Londres', 3),
(34, 'Londres', 'Porto', 3),
(35, 'Madrid', 'Londres', 3),
(36, 'Londres', 'Madrid', 3),
(37, 'Barcelona', 'Londres', 3),
(38, 'Londres', 'Barcelona', 3),
(39, 'Paris', 'Londres', 3),
(40, 'Londres', 'Paris', 3),
(41, 'Madrid', 'Bruxelas', 3),
(42, 'Bruxelas', 'Madrid', 3),
(43, 'Barcelona', 'Paris', 3),
(44, 'Paris', 'Barcelona', 3),
(45, 'Porto', 'Bruxelas', 3),
(46, 'Bruxelas', 'Porto', 3),
(47, 'Faro', 'Madrid', 3),
(48, 'Madrid', 'Faro', 3),
(49, 'Coimbra', 'Madrid', 3),
(50, 'Madrid', 'Coimbra', 3);

-- Inserir horários para as rotas
INSERT INTO horarios (
    id_rota, hora_partida, hora_chegada, capacidade_autocarro, lugares_disponiveis, preco,
    data_inicio, data_fim
) VALUES
(1, '08:00:00', '12:30:00', 50, 50, 60.00, '2025-04-10', NULL),
(2, '09:00:00', '15:30:00', 50, 50, 75.00, '2025-04-10', NULL),
(3, '10:00:00', '17:00:00', 50, 50, 85.00, '2025-04-10', NULL),
(4, '07:00:00', '18:00:00', 50, 50, 130.00, '2025-04-10', NULL),
(5, '06:00:00', '19:30:00', 50, 50, 140.00, '2025-04-10', NULL),
(6, '11:00:00', '18:00:00', 50, 50, 85.00, '2025-04-10', NULL),
(7, '12:00:00', '18:30:00', 50, 50, 75.00, '2025-04-10', NULL),
(8, '13:00:00', '18:00:00', 50, 50, 65.00, '2025-04-10', NULL),
(9, '14:00:00', '19:30:00', 50, 50, 70.00, '2025-04-10', NULL),
(10, '15:00:00', '21:00:00', 50, 50, 80.00, '2025-04-10', NULL),
(11, '06:30:00', '12:00:00', 50, 50, 55.00, '2025-04-10', NULL),
(12, '07:45:00', '13:30:00', 50, 50, 60.00, '2025-04-10', NULL),
(13, '09:15:00', '14:45:00', 50, 50, 70.00, '2025-04-10', NULL),
(14, '11:30:00', '16:00:00', 50, 50, 75.00, '2025-04-10', NULL),
(15, '13:45:00', '19:15:00', 50, 50, 85.00, '2025-04-10', NULL),
(16, '08:00:00', '13:00:00', 50, 50, 65.00, '2025-04-10', NULL),
(17, '10:00:00', '15:00:00', 50, 50, 70.00, '2025-04-10', NULL),
(18, '12:00:00', '17:00:00', 50, 50, 75.00, '2025-04-10', NULL),
(19, '14:00:00', '19:00:00', 50, 50, 80.00, '2025-04-10', NULL),
(20, '16:00:00', '21:00:00', 50, 50, 90.00, '2025-04-10', NULL),
(21, '06:00:00', '11:00:00', 50, 50, 50.00, '2025-04-10', NULL),
(22, '07:30:00', '12:30:00', 50, 50, 55.00, '2025-04-10', NULL),
(23, '09:00:00', '14:00:00', 50, 50, 60.00, '2025-04-10', NULL),
(24, '11:30:00', '16:30:00', 50, 50, 70.00, '2025-04-10', NULL),
(25, '13:00:00', '18:00:00', 50, 50, 75.00, '2025-04-10', NULL),
(26, '08:15:00', '13:45:00', 50, 50, 65.00, '2025-04-10', NULL),
(27, '10:30:00', '16:00:00', 50, 50, 70.00, '2025-04-10', NULL),
(28, '12:45:00', '18:15:00', 50, 50, 80.00, '2025-04-10', NULL),
(29, '14:30:00', '20:00:00', 50, 50, 85.00, '2025-04-10', NULL),
(30, '16:15:00', '21:45:00', 50, 50, 95.00, '2025-04-10', NULL),
(31, '06:45:00', '12:15:00', 50, 50, 55.00, '2025-04-10', NULL),
(32, '08:30:00', '14:00:00', 50, 50, 60.00, '2025-04-10', NULL),
(33, '10:15:00', '15:45:00', 50, 50, 70.00, '2025-04-10', NULL),
(34, '12:00:00', '17:30:00', 50, 50, 75.00, '2025-04-10', NULL),
(35, '13:45:00', '19:15:00', 50, 50, 85.00, '2025-04-10', NULL),
(36, '07:00:00', '12:30:00', 50, 50, 60.00, '2025-04-10', NULL),
(37, '09:30:00', '15:00:00', 50, 50, 70.00, '2025-04-10', NULL),
(38, '11:45:00', '17:15:00', 50, 50, 80.00, '2025-04-10', NULL),
(39, '13:30:00', '19:00:00', 50, 50, 85.00, '2025-04-10', NULL),
(40, '15:15:00', '20:45:00', 50, 50, 90.00, '2025-04-10', NULL),
(41, '07:30:00', '13:00:00', 50, 50, 55.00, '2025-04-10', NULL),
(42, '09:00:00', '14:30:00', 50, 50, 60.00, '2025-04-10', NULL),
(43, '10:45:00', '16:15:00', 50, 50, 70.00, '2025-04-10', NULL),
(44, '12:30:00', '18:00:00', 50, 50, 75.00, '2025-04-10', NULL),
(45, '14:15:00', '19:45:00', 50, 50, 85.00, '2025-04-10', NULL),
(46, '07:45:00', '13:15:00', 50, 50, 60.00, '2025-04-10', NULL),
(47, '09:30:00', '15:00:00', 50, 50, 70.00, '2025-04-10', NULL),
(48, '11:15:00', '16:45:00', 50, 50, 80.00, '2025-04-10', NULL),
(49, '13:00:00', '18:30:00', 50, 50, 85.00, '2025-04-10', NULL),
(50, '15:00:00', '22:00:00', 50, 50, 90.00, '2025-04-10', NULL);

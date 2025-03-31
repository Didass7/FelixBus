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
('cliente', md5('cliente'), 'cliente@gmail.com', 'cliente', '999888777', 'Rua 1', 'cliente')

INSERT INTO utilizadores 
(nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil) VALUES
 ('funcionario', MD5('funcionario'), 'funcionario@gmail.com', 'Funcionário', '999888777', 'Rua 2', 'funcionário');

 INSERT INTO utilizadores 
(nome_utilizador, hash_password, email, nome_completo, telefone, morada, perfil) VALUES 
('admin', MD5('admin'), 'admin@gmail.com', 'Administrador', '933333333', 'Rua 3', 'administrador');
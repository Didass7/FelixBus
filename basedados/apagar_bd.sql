-- Garantir que estamos usando a base de dados correta
USE felixbus;

-- Desativar verificação de chaves estrangeiras para permitir drop das tabelas
SET FOREIGN_KEY_CHECKS = 0;

-- Apagar todas as tabelas do projeto
DROP TABLE IF EXISTS bilhetes;
DROP TABLE IF EXISTS transacoes;
DROP TABLE IF EXISTS carteiras;
DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS horarios;
DROP TABLE IF EXISTS rotas;
DROP TABLE IF EXISTS utilizadores;

-- Reativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- Apagar a base de dados
DROP DATABASE IF EXISTS felixbus;

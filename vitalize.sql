-- Vitalize atualizado: instalação NOVA em banco vazio.
-- Não apaga dados e não substitui a migração de bancos existentes.
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS vitalize CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vitalize;

CREATE TABLE usuarios (id_usuario INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, sobrenome VARCHAR(100) NULL, email VARCHAR(150) NOT NULL UNIQUE, senha VARCHAR(255) NOT NULL, telefone VARCHAR(20) NULL, foto_perfil VARCHAR(255) NULL, token_verificacao VARCHAR(255) NULL, email_verificado TINYINT(1) NOT NULL DEFAULT 0, tipo ENUM('usuario','admin') NOT NULL DEFAULT 'usuario', data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP, session_version INT NOT NULL DEFAULT 0, foto_public_id VARCHAR(255) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE endereco (id_endereco INT AUTO_INCREMENT PRIMARY KEY, rua VARCHAR(150), numero VARCHAR(10), bairro VARCHAR(100), cidade VARCHAR(100), estado VARCHAR(2), cep VARCHAR(10)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grupos (id_grupo INT AUTO_INCREMENT PRIMARY KEY, id_criador INT NULL, nome_grupo VARCHAR(150) NOT NULL, mais_info TEXT, foco VARCHAR(100), data_encontro DATE, horario TIME, link VARCHAR(2048), telefone_grupo VARCHAR(150), imagem VARCHAR(255), imagem_public_id VARCHAR(255), status VARCHAR(15) NOT NULL DEFAULT 'pendente', INDEX(id_criador), FOREIGN KEY(id_criador) REFERENCES usuarios(id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE relatos (id_relato INT AUTO_INCREMENT PRIMARY KEY, id_usuario INT NOT NULL, titulo VARCHAR(150), relato TEXT NOT NULL, anonimo TINYINT(1) NOT NULL DEFAULT 0, data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP, status VARCHAR(15) NOT NULL DEFAULT 'pendente', INDEX(id_usuario), INDEX(data_publicacao), FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consultas (id_consulta INT AUTO_INCREMENT PRIMARY KEY, data DATE, horario TIME, medico VARCHAR(150), nome_local VARCHAR(150), id_endereco INT NULL, id_usuario INT NULL, tipo VARCHAR(20) NOT NULL DEFAULT 'Consulta', especialidade VARCHAR(100), INDEX(id_usuario,data), FOREIGN KEY(id_endereco) REFERENCES endereco(id_endereco), FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE participantes (id_grupo INT NOT NULL, id_usuario INT NOT NULL, criado_em DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id_grupo,id_usuario), FOREIGN KEY(id_grupo) REFERENCES grupos(id_grupo) ON DELETE CASCADE, FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tokens_conta (token_hash CHAR(64) PRIMARY KEY, id_usuario INT NOT NULL, finalidade VARCHAR(12) NOT NULL, expires_at BIGINT NOT NULL, FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_sessions (id CHAR(64) PRIMARY KEY, data MEDIUMBLOB NOT NULL, expires_at BIGINT NOT NULL, INDEX(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (bucket CHAR(64) PRIMARY KEY, hits INT NOT NULL, expires_at BIGINT NOT NULL, INDEX(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_usage (bucket VARCHAR(100) PRIMARY KEY, calls INT NOT NULL DEFAULT 0, tokens INT NOT NULL DEFAULT 0, expires_at BIGINT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_cache (id_usuario INT NOT NULL, cache_key CHAR(64) NOT NULL, resultado TEXT NOT NULL, expires_at BIGINT NOT NULL, PRIMARY KEY(id_usuario,cache_key), FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE denuncias (id INT AUTO_INCREMENT PRIMARY KEY, id_usuario INT NOT NULL, tipo VARCHAR(10) NOT NULL, alvo INT NOT NULL, motivo VARCHAR(1000) NOT NULL, resolvida TINYINT NOT NULL DEFAULT 0, criada_em DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_cleanup (public_id VARCHAR(255) PRIMARY KEY, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

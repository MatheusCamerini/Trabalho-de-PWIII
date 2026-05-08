-- ============================================================
--  Sistema de Inventário de Itens
--  db.sql — Criação do Banco de Dados e Tabela
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventario_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inventario_db;

CREATE TABLE IF NOT EXISTS itens (
    id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    nome       VARCHAR(120)     NOT NULL,
    categoria  VARCHAR(80)      NOT NULL,
    quantidade INT UNSIGNED     NOT NULL DEFAULT 0,
    preco      DECIMAL(10, 2)   NOT NULL DEFAULT 0.00,
    criado_em  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_categoria (categoria),
    INDEX idx_nome      (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Dados de exemplo ────────────────────────────────────────
INSERT INTO itens (nome, categoria, quantidade, preco) VALUES
  ('Teclado Mecânico RGB',      'Periféricos',   15,   349.90),
  ('Monitor Full HD 24"',       'Monitores',      8,  1199.00),
  ('Cadeira Gamer Ergonômica',  'Móveis',         3,   899.50),
  ('SSD NVMe 1TB',              'Armazenamento', 22,   459.90),
  ('Headset Sem Fio',           'Áudio',         10,   289.00),
  ('Webcam 1080p',              'Periféricos',    6,   219.90),
  ('Hub USB-C 7 Portas',        'Acessórios',    18,   129.90),
  ('Mousepad XL',               'Acessórios',    30,    59.90);

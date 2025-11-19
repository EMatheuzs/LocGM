-- Banco de dados para o projeto locGM
-- Gera esquema e dados iniciais baseados em `data.php` (sessão)
-- Sintaxe: MySQL 5.7+

CREATE DATABASE IF NOT EXISTS `locGM` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `locGM`;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS `empresas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `nome_empresa` VARCHAR(255) NOT NULL,
    `endereco` VARCHAR(255),
    `telefone` VARCHAR(50),
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `funcao` VARCHAR(20) NOT NULL DEFAULT 'visitante',
    `nome` VARCHAR(255),
    `nome_empresa` VARCHAR(255),
    `telefone` VARCHAR(50),
    `endereco` VARCHAR(255),
    `observacao_precos` TEXT,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de locais/lugares
CREATE TABLE IF NOT EXISTS `locais` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `tipo` VARCHAR(50) DEFAULT 'restaurante',
    `latitude` DECIMAL(10, 6) NOT NULL,
    `longitude` DECIMAL(10, 6) NOT NULL,
    `avaliacao` DECIMAL(3, 1) DEFAULT 0,
    `endereco` VARCHAR(255),
    `id_empresa` INT,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_empresa`) REFERENCES `empresas`(`id`) ON DELETE SET NULL,
    INDEX `idx_empresa` (`id_empresa`),
    INDEX `idx_localizacao` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de posts/promoções
CREATE TABLE IF NOT EXISTS `postagens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_empresa` INT,
    `nome_empresa` VARCHAR(255),
    `conteudo` TEXT,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_empresa`) REFERENCES `empresas`(`id`) ON DELETE SET NULL,
    INDEX `idx_empresa` (`id_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de mensagens (chat)
CREATE TABLE IF NOT EXISTS `mensagens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email_de` VARCHAR(255),
    `email_para` VARCHAR(255),
    `conteudo` TEXT,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_de` (`email_de`),
    INDEX `idx_para` (`email_para`),
    INDEX `idx_conversa` (`email_de`, `email_para`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de auditoria (log de alterações)
CREATE TABLE IF NOT EXISTS `log_auditoria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome_tabela` VARCHAR(50),
    `id_registro` INT,
    `acao` VARCHAR(20),
    `valores_antigos` JSON,
    `valores_novos` JSON,
    `alterado_por` VARCHAR(255),
    `alterado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== PROCEDURES ==========

-- PROCEDURE: Inserir local com validação
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `inserir_local` (
    IN p_nome VARCHAR(255),
    IN p_tipo VARCHAR(50),
    IN p_latitude DECIMAL(10, 6),
    IN p_longitude DECIMAL(10, 6),
    IN p_avaliacao DECIMAL(3, 1),
    IN p_endereco VARCHAR(255),
    IN p_id_empresa INT
)
BEGIN
    DECLARE v_erro INT DEFAULT 0;
    
    -- Validar latitude e longitude
    IF p_latitude < -90 OR p_latitude > 90 OR p_longitude < -180 OR p_longitude > 180 THEN
        SET v_erro = 1;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Coordenadas inválidas: latitude deve estar entre -90 e 90, longitude entre -180 e 180';
    END IF;
    
    -- Validar avaliação
    IF p_avaliacao < 0 OR p_avaliacao > 5 THEN
        SET v_erro = 1;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nota inválida: deve estar entre 0 e 5';
    END IF;
    
    -- Validar nome
    IF p_nome IS NULL OR TRIM(p_nome) = '' THEN
        SET v_erro = 1;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nome do local é obrigatório';
    END IF;
    
    IF v_erro = 0 THEN
        INSERT INTO `locais` (`nome`, `tipo`, `latitude`, `longitude`, `avaliacao`, `endereco`, `id_empresa`)
        VALUES (p_nome, p_tipo, p_latitude, p_longitude, p_avaliacao, p_endereco, p_id_empresa);
    END IF;
END //
DELIMITER ;

-- PROCEDURE: Obter locais próximos (raio em km)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `obter_locais_proximos` (
    IN p_latitude DECIMAL(10, 6),
    IN p_longitude DECIMAL(10, 6),
    IN p_raio_km DECIMAL(10, 2)
)
BEGIN
    SELECT 
        `id`,
        `nome`,
        `tipo`,
        `latitude`,
        `longitude`,
        `avaliacao`,
        `endereco`,
        `id_empresa`,
        -- Calcular distância em km usando fórmula de Haversine
        (6371 * ACOS(
            COS(RADIANS(90 - p_latitude)) * 
            COS(RADIANS(90 - `latitude`)) +
            SIN(RADIANS(90 - p_latitude)) * 
            SIN(RADIANS(90 - `latitude`)) * 
            COS(RADIANS(p_longitude - `longitude`))
        )) AS `distancia_km`
    FROM `locais`
    HAVING `distancia_km` <= p_raio_km
    ORDER BY `distancia_km` ASC;
END //
DELIMITER ;

-- PROCEDURE: Contar locais por empresa
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `contar_locais_por_empresa` (
    IN p_id_empresa INT
)
BEGIN
    SELECT 
        e.`id`,
        e.`nome_empresa`,
        e.`email`,
        COUNT(l.`id`) AS `total_locais`,
        AVG(l.`avaliacao`) AS `media_avaliacao`
    FROM `empresas` e
    LEFT JOIN `locais` l ON e.`id` = l.`id_empresa`
    WHERE e.`id` = p_id_empresa
    GROUP BY e.`id`, e.`nome_empresa`, e.`email`;
END //
DELIMITER ;

-- PROCEDURE: Deletar empresa e cascata
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `deletar_empresa_cascata` (
    IN p_id_empresa INT
)
BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
    
    START TRANSACTION;
    
    -- Deletar locais
    DELETE FROM `locais` WHERE `id_empresa` = p_id_empresa;
    
    -- Deletar postagens
    DELETE FROM `postagens` WHERE `id_empresa` = p_id_empresa;
    
    -- Deletar empresa
    DELETE FROM `empresas` WHERE `id` = p_id_empresa;
    
    COMMIT;
END //
DELIMITER ;

-- PROCEDURE: Estatísticas gerais
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `obter_estatisticas` ()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM `empresas`) AS `total_empresas`,
        (SELECT COUNT(*) FROM `usuarios`) AS `total_usuarios`,
        (SELECT COUNT(*) FROM `locais`) AS `total_locais`,
        (SELECT COUNT(*) FROM `postagens`) AS `total_postagens`,
        (SELECT AVG(`avaliacao`) FROM `locais`) AS `media_avaliacao`,
        NOW() AS `ultima_atualizacao`;
END //
DELIMITER ;

-- ========== TRIGGERS ==========

-- TRIGGER: Atualizar nome_empresa em postagens quando empresa é renomeada
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_atualizar_nome_postagem` 
AFTER UPDATE ON `empresas`
FOR EACH ROW
BEGIN
    IF OLD.`nome_empresa` != NEW.`nome_empresa` THEN
        UPDATE `postagens` 
        SET `nome_empresa` = NEW.`nome_empresa`
        WHERE `id_empresa` = NEW.`id`;
    END IF;
END //
DELIMITER ;

-- TRIGGER: Validar avaliação antes de inserir local
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_validar_avaliacao_insercao`
BEFORE INSERT ON `locais`
FOR EACH ROW
BEGIN
    IF NEW.`avaliacao` < 0 OR NEW.`avaliacao` > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nota deve estar entre 0 e 5';
    END IF;
    
    IF NEW.`latitude` < -90 OR NEW.`latitude` > 90 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Latitude inválida';
    END IF;
    
    IF NEW.`longitude` < -180 OR NEW.`longitude` > 180 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Longitude inválida';
    END IF;
END //
DELIMITER ;

-- TRIGGER: Validar avaliação antes de atualizar local
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_validar_avaliacao_atualizacao`
BEFORE UPDATE ON `locais`
FOR EACH ROW
BEGIN
    IF NEW.`avaliacao` < 0 OR NEW.`avaliacao` > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nota deve estar entre 0 e 5';
    END IF;
    
    IF NEW.`latitude` < -90 OR NEW.`latitude` > 90 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Latitude inválida';
    END IF;
    
    IF NEW.`longitude` < -180 OR NEW.`longitude` > 180 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Longitude inválida';
    END IF;
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para inserção em empresas
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_auditoria_empresas_insercao`
AFTER INSERT ON `empresas`
FOR EACH ROW
BEGIN
    INSERT INTO `log_auditoria` (`nome_tabela`, `id_registro`, `acao`, `valores_novos`, `alterado_em`)
    VALUES (
        'empresas',
        NEW.`id`,
        'INSERCAO',
        JSON_OBJECT(
            'id', NEW.`id`,
            'email', NEW.`email`,
            'nome_empresa', NEW.`nome_empresa`,
            'endereco', NEW.`endereco`,
            'telefone', NEW.`telefone`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para atualização em empresas
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_auditoria_empresas_atualizacao`
AFTER UPDATE ON `empresas`
FOR EACH ROW
BEGIN
    INSERT INTO `log_auditoria` (`nome_tabela`, `id_registro`, `acao`, `valores_antigos`, `valores_novos`, `alterado_em`)
    VALUES (
        'empresas',
        NEW.`id`,
        'ATUALIZACAO',
        JSON_OBJECT(
            'email', OLD.`email`,
            'nome_empresa', OLD.`nome_empresa`,
            'endereco', OLD.`endereco`,
            'telefone', OLD.`telefone`
        ),
        JSON_OBJECT(
            'email', NEW.`email`,
            'nome_empresa', NEW.`nome_empresa`,
            'endereco', NEW.`endereco`,
            'telefone', NEW.`telefone`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para exclusão em empresas
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_auditoria_empresas_exclusao`
BEFORE DELETE ON `empresas`
FOR EACH ROW
BEGIN
    INSERT INTO `log_auditoria` (`nome_tabela`, `id_registro`, `acao`, `valores_antigos`, `alterado_em`)
    VALUES (
        'empresas',
        OLD.`id`,
        'EXCLUSAO',
        JSON_OBJECT(
            'id', OLD.`id`,
            'email', OLD.`email`,
            'nome_empresa', OLD.`nome_empresa`,
            'endereco', OLD.`endereco`,
            'telefone', OLD.`telefone`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para inserção em locais
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_auditoria_locais_insercao`
AFTER INSERT ON `locais`
FOR EACH ROW
BEGIN
    INSERT INTO `log_auditoria` (`nome_tabela`, `id_registro`, `acao`, `valores_novos`, `alterado_em`)
    VALUES (
        'locais',
        NEW.`id`,
        'INSERCAO',
        JSON_OBJECT(
            'id', NEW.`id`,
            'nome', NEW.`nome`,
            'tipo', NEW.`tipo`,
            'latitude', NEW.`latitude`,
            'longitude', NEW.`longitude`,
            'avaliacao', NEW.`avaliacao`,
            'id_empresa', NEW.`id_empresa`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para atualização em locais
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_auditoria_locais_atualizacao`
AFTER UPDATE ON `locais`
FOR EACH ROW
BEGIN
    INSERT INTO `log_auditoria` (`nome_tabela`, `id_registro`, `acao`, `valores_antigos`, `valores_novos`, `alterado_em`)
    VALUES (
        'locais',
        NEW.`id`,
        'ATUALIZACAO',
        JSON_OBJECT(
            'nome', OLD.`nome`,
            'tipo', OLD.`tipo`,
            'latitude', OLD.`latitude`,
            'longitude', OLD.`longitude`,
            'avaliacao', OLD.`avaliacao`
        ),
        JSON_OBJECT(
            'nome', NEW.`nome`,
            'tipo', NEW.`tipo`,
            'latitude', NEW.`latitude`,
            'longitude', NEW.`longitude`,
            'avaliacao', NEW.`avaliacao`
        ),
        NOW()
    );
END //
DELIMITER ;

-- Dados iniciais (copiados do seed em data.php)
INSERT INTO `empresas` (`email`, `nome_empresa`, `endereco`, `telefone`) VALUES
('cheff@locgm.com', 'Restaurante Cheff LocGM', 'Av. Quintino Bocaiúva, Centro', '(69) 99999-0001'),
('pousada@locgm.com', 'Pousada Rio Mamoré', 'R. Cunha Matos', '(69) 99999-0002'),
('mercado@locgm.com', 'Mercado Bom Preço', 'Av. Costa Marques', '(69) 99999-0003');

INSERT INTO `usuarios` (`email`, `funcao`, `nome`, `telefone`) VALUES
('visitante@locgm.com', 'visitante', 'Visitante Demo', '(69) 90000-0000');

INSERT INTO `locais` (`nome`, `tipo`, `latitude`, `longitude`, `avaliacao`, `endereco`, `id_empresa`) VALUES
('Cheff LocGM', 'restaurante', -10.783500, -65.338000, 4.6, 'Centro', (SELECT `id` FROM `empresas` WHERE `email`='cheff@locgm.com')),
('Pousada Rio Mamoré', 'pousada', -10.781900, -65.339500, 4.4, 'Centro', (SELECT `id` FROM `empresas` WHERE `email`='pousada@locgm.com')),
('Mercado Bom Preço', 'mercado', -10.784200, -65.335500, 4.1, 'Centro', (SELECT `id` FROM `empresas` WHERE `email`='mercado@locgm.com')),
('Farmácia Mamoré', 'farmacia', -10.782800, -65.336800, 4.3, 'Centro', NULL),
('Praça do Madeira', 'turismo', -10.785200, -65.340100, 4.7, 'Praça', NULL);

INSERT INTO `postagens` (`id_empresa`, `nome_empresa`, `conteudo`) VALUES
((SELECT `id` FROM `empresas` WHERE `email`='cheff@locgm.com'), 'Restaurante Cheff LocGM', 'Promoção: almoço executivo 12h - 14h por R$ 24,90!'),
((SELECT `id` FROM `empresas` WHERE `email`='pousada@locgm.com'), 'Pousada Rio Mamoré', 'Hospedagem com vista para o Rio Mamoré! Aproveite nosso promocional de Verão.'),
((SELECT `id` FROM `empresas` WHERE `email`='mercado@locgm.com'), 'Mercado Bom Preço', 'Compre mais, pague menos! Frutas e verduras frescas todos os dias.');

INSERT INTO `mensagens` (`email_de`, `email_para`, `conteudo`) VALUES
('visitante@locgm.com', 'cheff@locgm.com', 'Olá, vocês fazem delivery?'),
('cheff@locgm.com', 'visitante@locgm.com', 'Sim! Temos horário de 11h a 22h. Qual o seu pedido?');

-- Criar índices para melhorar performance
CREATE INDEX `idx_email` ON `empresas`(`email`);
CREATE INDEX `idx_email_usuario` ON `usuarios`(`email`);
CREATE INDEX `idx_funcao` ON `usuarios`(`funcao`);



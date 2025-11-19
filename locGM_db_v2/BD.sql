-- Banco de dados para o projeto locGM
-- Gera esquema e dados iniciais baseados em `data.php` (sessão)
-- Sintaxe: MySQL 5.7+

CREATE DATABASE IF NOT EXISTS `locGM` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `locGM`;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `company_name` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255),
    `phone` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `role` VARCHAR(20) NOT NULL DEFAULT 'visitante',
    `name` VARCHAR(255),
    `company_name` VARCHAR(255),
    `phone` VARCHAR(50),
    `address` VARCHAR(255),
    `prices_note` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de locais/lugares
CREATE TABLE IF NOT EXISTS `places` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) DEFAULT 'restaurante',
    `lat` DECIMAL(10, 6) NOT NULL,
    `lng` DECIMAL(10, 6) NOT NULL,
    `rating` DECIMAL(3, 1) DEFAULT 0,
    `address` VARCHAR(255),
    `company_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_location` (`lat`, `lng`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de posts/promoções
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT,
    `company_name` VARCHAR(255),
    `content` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    INDEX `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de mensagens (chat)
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `from_email` VARCHAR(255),
    `to_email` VARCHAR(255),
    `content` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_from` (`from_email`),
    INDEX `idx_to` (`to_email`),
    INDEX `idx_conversation` (`from_email`, `to_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de auditoria (log de alterações)
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_name` VARCHAR(50),
    `record_id` INT,
    `action` VARCHAR(20),
    `old_values` JSON,
    `new_values` JSON,
    `changed_by` VARCHAR(255),
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== PROCEDURES ==========

-- PROCEDURE: Inserir lugar com validação
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `insert_place` (
    IN p_name VARCHAR(255),
    IN p_type VARCHAR(50),
    IN p_lat DECIMAL(10, 6),
    IN p_lng DECIMAL(10, 6),
    IN p_rating DECIMAL(3, 1),
    IN p_address VARCHAR(255),
    IN p_company_id INT
)
BEGIN
    DECLARE v_error INT DEFAULT 0;
    
    -- Validar latitude e longitude
    IF p_lat < -90 OR p_lat > 90 OR p_lng < -180 OR p_lng > 180 THEN
        SET v_error = 1;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Coordenadas inválidas: latitude deve estar entre -90 e 90, longitude entre -180 e 180';
    END IF;
    
    -- Validar rating
    IF p_rating < 0 OR p_rating > 5 THEN
        SET v_error = 1;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nota inválida: deve estar entre 0 e 5';
    END IF;
    
    -- Validar nome
    IF p_name IS NULL OR TRIM(p_name) = '' THEN
        SET v_error = 1;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nome do local é obrigatório';
    END IF;
    
    IF v_error = 0 THEN
        INSERT INTO `places` (`name`, `type`, `lat`, `lng`, `rating`, `address`, `company_id`)
        VALUES (p_name, p_type, p_lat, p_lng, p_rating, p_address, p_company_id);
    END IF;
END //
DELIMITER ;

-- PROCEDURE: Obter lugares próximos (raio em km)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `get_nearby_places` (
    IN p_lat DECIMAL(10, 6),
    IN p_lng DECIMAL(10, 6),
    IN p_radius_km DECIMAL(10, 2)
)
BEGIN
    SELECT 
        `id`,
        `name`,
        `type`,
        `lat`,
        `lng`,
        `rating`,
        `address`,
        `company_id`,
        -- Calcular distância em km usando fórmula de Haversine
        (6371 * ACOS(
            COS(RADIANS(90 - p_lat)) * 
            COS(RADIANS(90 - `lat`)) +
            SIN(RADIANS(90 - p_lat)) * 
            SIN(RADIANS(90 - `lat`)) * 
            COS(RADIANS(p_lng - `lng`))
        )) AS `distance_km`
    FROM `places`
    HAVING `distance_km` <= p_radius_km
    ORDER BY `distance_km` ASC;
END //
DELIMITER ;

-- PROCEDURE: Contar lugares por empresa
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `count_places_by_company` (
    IN p_company_id INT
)
BEGIN
    SELECT 
        c.`id`,
        c.`company_name`,
        c.`email`,
        COUNT(p.`id`) AS `total_places`,
        AVG(p.`rating`) AS `avg_rating`
    FROM `companies` c
    LEFT JOIN `places` p ON c.`id` = p.`company_id`
    WHERE c.`id` = p_company_id
    GROUP BY c.`id`, c.`company_name`, c.`email`;
END //
DELIMITER ;

-- PROCEDURE: Deletar empresa e cascata
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `delete_company_cascade` (
    IN p_company_id INT
)
BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;
    
    START TRANSACTION;
    
    -- Deletar lugares
    DELETE FROM `places` WHERE `company_id` = p_company_id;
    
    -- Deletar posts
    DELETE FROM `posts` WHERE `company_id` = p_company_id;
    
    -- Deletar empresa
    DELETE FROM `companies` WHERE `id` = p_company_id;
    
    COMMIT;
END //
DELIMITER ;

-- PROCEDURE: Estatísticas gerais
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `get_statistics` ()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM `companies`) AS `total_companies`,
        (SELECT COUNT(*) FROM `users`) AS `total_users`,
        (SELECT COUNT(*) FROM `places`) AS `total_places`,
        (SELECT COUNT(*) FROM `posts`) AS `total_posts`,
        (SELECT AVG(`rating`) FROM `places`) AS `avg_rating`,
        NOW() AS `last_updated`;
END //
DELIMITER ;

-- ========== TRIGGERS ==========

-- TRIGGER: Atualizar company_name em posts quando empresa é renomeada
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_update_post_company_name` 
AFTER UPDATE ON `companies`
FOR EACH ROW
BEGIN
    IF OLD.`company_name` != NEW.`company_name` THEN
        UPDATE `posts` 
        SET `company_name` = NEW.`company_name`
        WHERE `company_id` = NEW.`id`;
    END IF;
END //
DELIMITER ;

-- TRIGGER: Validar rating antes de inserir lugar
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_validate_place_rating_insert`
BEFORE INSERT ON `places`
FOR EACH ROW
BEGIN
    IF NEW.`rating` < 0 OR NEW.`rating` > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nota deve estar entre 0 e 5';
    END IF;
    
    IF NEW.`lat` < -90 OR NEW.`lat` > 90 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Latitude inválida';
    END IF;
    
    IF NEW.`lng` < -180 OR NEW.`lng` > 180 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Longitude inválida';
    END IF;
END //
DELIMITER ;

-- TRIGGER: Validar rating antes de atualizar lugar
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_validate_place_rating_update`
BEFORE UPDATE ON `places`
FOR EACH ROW
BEGIN
    IF NEW.`rating` < 0 OR NEW.`rating` > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nota deve estar entre 0 e 5';
    END IF;
    
    IF NEW.`lat` < -90 OR NEW.`lat` > 90 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Latitude inválida';
    END IF;
    
    IF NEW.`lng` < -180 OR NEW.`lng` > 180 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Longitude inválida';
    END IF;
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para inserts em empresas
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_audit_companies_insert`
AFTER INSERT ON `companies`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`table_name`, `record_id`, `action`, `new_values`, `changed_at`)
    VALUES (
        'companies',
        NEW.`id`,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.`id`,
            'email', NEW.`email`,
            'company_name', NEW.`company_name`,
            'address', NEW.`address`,
            'phone', NEW.`phone`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para updates em empresas
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_audit_companies_update`
AFTER UPDATE ON `companies`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`table_name`, `record_id`, `action`, `old_values`, `new_values`, `changed_at`)
    VALUES (
        'companies',
        NEW.`id`,
        'UPDATE',
        JSON_OBJECT(
            'email', OLD.`email`,
            'company_name', OLD.`company_name`,
            'address', OLD.`address`,
            'phone', OLD.`phone`
        ),
        JSON_OBJECT(
            'email', NEW.`email`,
            'company_name', NEW.`company_name`,
            'address', NEW.`address`,
            'phone', NEW.`phone`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para deletes em empresas
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_audit_companies_delete`
BEFORE DELETE ON `companies`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`table_name`, `record_id`, `action`, `old_values`, `changed_at`)
    VALUES (
        'companies',
        OLD.`id`,
        'DELETE',
        JSON_OBJECT(
            'id', OLD.`id`,
            'email', OLD.`email`,
            'company_name', OLD.`company_name`,
            'address', OLD.`address`,
            'phone', OLD.`phone`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para inserts em places
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_audit_places_insert`
AFTER INSERT ON `places`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`table_name`, `record_id`, `action`, `new_values`, `changed_at`)
    VALUES (
        'places',
        NEW.`id`,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.`id`,
            'name', NEW.`name`,
            'type', NEW.`type`,
            'lat', NEW.`lat`,
            'lng', NEW.`lng`,
            'rating', NEW.`rating`,
            'company_id', NEW.`company_id`
        ),
        NOW()
    );
END //
DELIMITER ;

-- TRIGGER: Log de auditoria para updates em places
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `trg_audit_places_update`
AFTER UPDATE ON `places`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_log` (`table_name`, `record_id`, `action`, `old_values`, `new_values`, `changed_at`)
    VALUES (
        'places',
        NEW.`id`,
        'UPDATE',
        JSON_OBJECT(
            'name', OLD.`name`,
            'type', OLD.`type`,
            'lat', OLD.`lat`,
            'lng', OLD.`lng`,
            'rating', OLD.`rating`
        ),
        JSON_OBJECT(
            'name', NEW.`name`,
            'type', NEW.`type`,
            'lat', NEW.`lat`,
            'lng', NEW.`lng`,
            'rating', NEW.`rating`
        ),
        NOW()
    );
END //
DELIMITER ;

-- Dados iniciais (copiados do seed em data.php)
INSERT INTO `companies` (`email`, `company_name`, `address`, `phone`) VALUES
('cheff@locgm.com', 'Restaurante Cheff LocGM', 'Av. Quintino Bocaiúva, Centro', '(69) 99999-0001'),
('pousada@locgm.com', 'Pousada Rio Mamoré', 'R. Cunha Matos', '(69) 99999-0002'),
('mercado@locgm.com', 'Mercado Bom Preço', 'Av. Costa Marques', '(69) 99999-0003');

INSERT INTO `users` (`email`, `role`, `name`, `phone`) VALUES
('visitante@locgm.com', 'visitante', 'Visitante Demo', '(69) 90000-0000');

INSERT INTO `places` (`name`, `type`, `lat`, `lng`, `rating`, `address`, `company_id`) VALUES
('Cheff LocGM', 'restaurante', -10.783500, -65.338000, 4.6, 'Centro', (SELECT `id` FROM `companies` WHERE `email`='cheff@locgm.com')),
('Pousada Rio Mamoré', 'pousada', -10.781900, -65.339500, 4.4, 'Centro', (SELECT `id` FROM `companies` WHERE `email`='pousada@locgm.com')),
('Mercado Bom Preço', 'mercado', -10.784200, -65.335500, 4.1, 'Centro', (SELECT `id` FROM `companies` WHERE `email`='mercado@locgm.com')),
('Farmácia Mamoré', 'farmacia', -10.782800, -65.336800, 4.3, 'Centro', NULL),
('Praça do Madeira', 'turismo', -10.785200, -65.340100, 4.7, 'Praça', NULL);

INSERT INTO `posts` (`company_id`, `company_name`, `content`) VALUES
((SELECT `id` FROM `companies` WHERE `email`='cheff@locgm.com'), 'Restaurante Cheff LocGM', 'Promoção: almoço executivo 12h - 14h por R$ 24,90!'),
((SELECT `id` FROM `companies` WHERE `email`='pousada@locgm.com'), 'Pousada Rio Mamoré', 'Hospedagem com vista para o Rio Mamoré! Aproveite nosso promocional de Verão.'),
((SELECT `id` FROM `companies` WHERE `email`='mercado@locgm.com'), 'Mercado Bom Preço', 'Compre mais, pague menos! Frutas e verduras frescas todos os dias.');

INSERT INTO `messages` (`from_email`, `to_email`, `content`) VALUES
('visitante@locgm.com', 'cheff@locgm.com', 'Olá, vocês fazem delivery?'),
('cheff@locgm.com', 'visitante@locgm.com', 'Sim! Temos horário de 11h a 22h. Qual o seu pedido?');

-- Criar índices para melhorar performance
CREATE INDEX `idx_email` ON `companies`(`email`);
CREATE INDEX `idx_user_email` ON `users`(`email`);
CREATE INDEX `idx_role` ON `users`(`role`);



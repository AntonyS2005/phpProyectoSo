USE userDB;

SET @has_user_agent := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sesiones'
      AND COLUMN_NAME = 'user_agent'
);
SET @sql := IF(@has_user_agent = 0,
    'ALTER TABLE sesiones ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_last_activity := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sesiones'
      AND COLUMN_NAME = 'fecha_ultima_actividad'
);
SET @sql := IF(@has_last_activity = 0,
    'ALTER TABLE sesiones ADD COLUMN fecha_ultima_actividad DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER fecha_inicio',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_revoked_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sesiones'
      AND COLUMN_NAME = 'fecha_revocacion'
);
SET @sql := IF(@has_revoked_at = 0,
    'ALTER TABLE sesiones ADD COLUMN fecha_revocacion DATETIME NULL AFTER fecha_expiracion',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_reason := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sesiones'
      AND COLUMN_NAME = 'motivo_revocacion'
);
SET @sql := IF(@has_reason = 0,
    'ALTER TABLE sesiones ADD COLUMN motivo_revocacion VARCHAR(150) NULL AFTER fecha_revocacion',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE sesiones MODIFY token_hash VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id_refresh_token  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_sesion         INT UNSIGNED    NULL,
    id_usuario        INT UNSIGNED    NOT NULL,
    token_hash        CHAR(64)        NOT NULL,
    ip_address        VARCHAR(45)     NOT NULL,
    user_agent        VARCHAR(255)        NULL,
    activa            TINYINT(1)      NOT NULL DEFAULT 1,
    fecha_creacion    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion  DATETIME        NOT NULL,
    fecha_revocacion  DATETIME            NULL,
    PRIMARY KEY (id_refresh_token),
    UNIQUE KEY uq_refresh_token_hash (token_hash),
    INDEX idx_refresh_sesion (id_sesion),
    INDEX idx_refresh_usuario (id_usuario),
    INDEX idx_refresh_activa_expira (activa, fecha_expiracion),
    CONSTRAINT fk_refresh_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS access_tokens (
    id_access_token   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_refresh_token  BIGINT UNSIGNED NOT NULL,
    id_usuario        INT UNSIGNED    NOT NULL,
    token_hash        CHAR(64)        NOT NULL,
    ip_address        VARCHAR(45)     NOT NULL,
    fecha_creacion    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion  DATETIME        NOT NULL,
    PRIMARY KEY (id_access_token),
    UNIQUE KEY uq_access_token_hash (token_hash),
    INDEX idx_access_usuario (id_usuario),
    INDEX idx_access_expira (fecha_expiracion),
    CONSTRAINT fk_access_refresh FOREIGN KEY (id_refresh_token) REFERENCES refresh_tokens (id_refresh_token) ON DELETE CASCADE,
    CONSTRAINT fk_access_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

SET @has_session_column := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'refresh_tokens'
      AND COLUMN_NAME = 'id_sesion'
);
SET @sql := IF(@has_session_column = 0,
    'ALTER TABLE refresh_tokens ADD COLUMN id_sesion INT UNSIGNED NULL AFTER id_refresh_token',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_session_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'refresh_tokens'
      AND INDEX_NAME = 'idx_refresh_sesion'
);
SET @sql := IF(@has_session_index = 0,
    'ALTER TABLE refresh_tokens ADD INDEX idx_refresh_sesion (id_sesion)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE refresh_tokens rt
JOIN (
    SELECT id_usuario, MIN(id_sesion) AS id_sesion
    FROM sesiones
    GROUP BY id_usuario
) s ON s.id_usuario = rt.id_usuario
SET rt.id_sesion = COALESCE(rt.id_sesion, s.id_sesion)
WHERE rt.id_sesion IS NULL;

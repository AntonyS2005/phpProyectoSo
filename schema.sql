-- ================================================
-- SCHEMA  —  Estructura de la base de datos
-- ================================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS userDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE userDB;

-- 1. Estados de usuario
CREATE TABLE cat_status (
    id_status   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(30)      NOT NULL,
    PRIMARY KEY (id_status),
    UNIQUE KEY uq_status_nombre (nombre)
) ENGINE=InnoDB;

-- 2. Roles
CREATE TABLE roles (
    id_rol      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(50)  NOT NULL,
    descripcion VARCHAR(150)     NULL,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB;

-- 3. Catálogo de recursos
--    Son las "cosas" del sistema sobre las que puedes operar
CREATE TABLE cat_recurso (
    id_recurso  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(60)      NOT NULL,
    descripcion VARCHAR(150)         NULL,
    PRIMARY KEY (id_recurso),
    UNIQUE KEY uq_recurso_nombre (nombre)
) ENGINE=InnoDB;

-- 4. Catálogo de acciones
--    Son las operaciones posibles sobre cualquier recurso
CREATE TABLE cat_accion (
    id_accion   TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(20)      NOT NULL,
    descripcion VARCHAR(150)         NULL,
    PRIMARY KEY (id_accion),
    UNIQUE KEY uq_accion_nombre (nombre)
) ENGINE=InnoDB;

-- 5. Relacion recurso + accion valida
CREATE TABLE recurso_accion (
    id_recurso_accion INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_recurso        TINYINT UNSIGNED NOT NULL,
    id_accion         TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_recurso_accion),
    UNIQUE KEY uq_recurso_accion (id_recurso, id_accion),
    CONSTRAINT fk_recurso_accion_recurso FOREIGN KEY (id_recurso) REFERENCES cat_recurso (id_recurso) ON DELETE CASCADE,
    CONSTRAINT fk_recurso_accion_accion FOREIGN KEY (id_accion) REFERENCES cat_accion (id_accion) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Permisos  =  rol + recurso + accion  (combinación única)
CREATE TABLE permisos (
    id_permiso  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_rol      INT UNSIGNED     NOT NULL,
    id_recurso  TINYINT UNSIGNED NOT NULL,
    id_accion   TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_permiso),
    UNIQUE KEY uq_permiso_unico (id_rol, id_recurso, id_accion),
    CONSTRAINT fk_permisos_rol     FOREIGN KEY (id_rol)     REFERENCES roles       (id_rol)     ON DELETE CASCADE,
    CONSTRAINT fk_permisos_recurso FOREIGN KEY (id_recurso) REFERENCES cat_recurso (id_recurso),
    CONSTRAINT fk_permisos_accion  FOREIGN KEY (id_accion)  REFERENCES cat_accion  (id_accion)
) ENGINE=InnoDB;

-- 7. Usuarios
CREATE TABLE usuarios (
    id_usuario          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_status           TINYINT UNSIGNED NOT NULL,
    id_rol              INT UNSIGNED     NOT NULL,
    username            VARCHAR(80)      NOT NULL,
    email               VARCHAR(150)     NOT NULL,
    password_hash       VARCHAR(255)     NOT NULL,
    email_verificado    TINYINT(1)       NOT NULL DEFAULT 0,
    fecha_registro      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_acceso DATETIME             NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_username (username),
    UNIQUE KEY uq_usuarios_email    (email),
    CONSTRAINT fk_usuarios_status FOREIGN KEY (id_status) REFERENCES cat_status (id_status),
    CONSTRAINT fk_usuarios_rol    FOREIGN KEY (id_rol)    REFERENCES roles       (id_rol)
) ENGINE=InnoDB;

-- 8. Perfil extendido (1:1 con usuarios, separado por 3FN)
CREATE TABLE usuarios_perfil (
    id_usuario       INT UNSIGNED NOT NULL,
    nombre           VARCHAR(80)      NULL,
    apellido         VARCHAR(80)      NULL,
    telefono         VARCHAR(20)      NULL,
    fecha_nacimiento DATE             NULL,
    PRIMARY KEY (id_usuario),
    CONSTRAINT fk_perfil_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Sesiones
CREATE TABLE sesiones (
    id_sesion        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario       INT UNSIGNED NOT NULL,
    token_hash       VARCHAR(255)     NULL,
    ip_address       VARCHAR(45)  NOT NULL,
    user_agent       VARCHAR(255)     NULL,
    activa           TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_inicio     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actividad DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME     NOT NULL,
    fecha_revocacion DATETIME         NULL,
    motivo_revocacion VARCHAR(150)    NULL,
    PRIMARY KEY (id_sesion),
    INDEX idx_token   (token_hash),
    INDEX idx_usuario (id_usuario),
    CONSTRAINT fk_sesiones_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9.1 Refresh tokens (24 horas)
CREATE TABLE refresh_tokens (
    id_refresh_token  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_sesion         INT UNSIGNED    NOT NULL,
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
    CONSTRAINT fk_refresh_sesion FOREIGN KEY (id_sesion) REFERENCES sesiones (id_sesion) ON DELETE CASCADE,
    CONSTRAINT fk_refresh_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9.2 Access tokens (15 minutos)
CREATE TABLE access_tokens (
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

-- 10. Auditoría
CREATE TABLE auditoria (
    id_auditoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario   INT UNSIGNED     NULL,
    accion       VARCHAR(100) NOT NULL,
    detalle      VARCHAR(255)     NULL,
    ip_address   VARCHAR(45)      NULL,
    fecha_evento DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    INDEX idx_usuario (id_usuario),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;


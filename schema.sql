-- ================================================
-- SCHEMA  —  Estructura de la base de datos
-- ================================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS userDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE userDB;

-- Tabla de estados
CREATE TABLE IF NOT EXISTS cat_status (
    id_status INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id_rol INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    id_status INT NOT NULL DEFAULT 1,
    id_rol INT NOT NULL DEFAULT 3,
    email_verificado TINYINT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_status) REFERENCES cat_status(id_status),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (id_status),
    INDEX idx_rol (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de perfiles de usuarios
CREATE TABLE IF NOT EXISTS usuarios_perfil (
    id_perfil INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL UNIQUE,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de recursos
CREATE TABLE IF NOT EXISTS cat_recurso (
    id_recurso INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de acciones
CREATE TABLE IF NOT EXISTS cat_accion (
    id_accion INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos
CREATE TABLE IF NOT EXISTS permisos (
    id_permiso INT PRIMARY KEY AUTO_INCREMENT,
    id_rol INT NOT NULL,
    id_recurso INT NOT NULL,
    id_accion INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_permiso (id_rol, id_recurso, id_accion),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    FOREIGN KEY (id_recurso) REFERENCES cat_recurso(id_recurso) ON DELETE CASCADE,
    FOREIGN KEY (id_accion) REFERENCES cat_accion(id_accion) ON DELETE CASCADE,
    INDEX idx_rol (id_rol),
    INDEX idx_recurso (id_recurso),
    INDEX idx_accion (id_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de auditoría
CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT,
    accion VARCHAR(100) NOT NULL,
    detalle TEXT,
    ip_address VARCHAR(45),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_usuario (id_usuario),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha_accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


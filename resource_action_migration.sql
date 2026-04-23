USE userDB;

CREATE TABLE IF NOT EXISTS recurso_accion (
    id_recurso_accion INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_recurso        TINYINT UNSIGNED NOT NULL,
    id_accion         TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_recurso_accion),
    UNIQUE KEY uq_recurso_accion (id_recurso, id_accion),
    CONSTRAINT fk_recurso_accion_recurso FOREIGN KEY (id_recurso) REFERENCES cat_recurso (id_recurso) ON DELETE CASCADE,
    CONSTRAINT fk_recurso_accion_accion FOREIGN KEY (id_accion) REFERENCES cat_accion (id_accion) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO recurso_accion (id_recurso, id_accion)
SELECT r.id_recurso, a.id_accion
FROM cat_recurso r
JOIN cat_accion a;

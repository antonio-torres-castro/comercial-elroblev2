CREATE TABLE cumplimiento_lecturas (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    cumplimiento_documento_version_id INT NOT NULL,
    fecha_inicio_lectura DATETIME NULL,
    fecha_fin_lectura DATETIME NULL,
    fecha_aceptacion DATETIME NULL,
    password_confirmado TINYINT NOT NULL DEFAULT 0,
    puntaje_obtenido DECIMAL(5,2) NULL,
    aprobado TINYINT NOT NULL DEFAULT 0,
    fecha_vencimiento DATE NULL,
    codigo_certificado VARCHAR(100) NULL,
    certificado_generado TINYINT NOT NULL DEFAULT 0,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    observaciones VARCHAR(1000) NULL,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lecturas_usuario (usuario_id),
    KEY idx_lecturas_version (cumplimiento_documento_version_id),
    KEY idx_lecturas_vencimiento (fecha_vencimiento),
    CONSTRAINT fk_lectura_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id),
    CONSTRAINT fk_lectura_version
        FOREIGN KEY (cumplimiento_documento_version_id)
        REFERENCES cumplimiento_documento_versiones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


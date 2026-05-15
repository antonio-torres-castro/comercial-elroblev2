CREATE TABLE cumplimiento_preguntas (
    id INT NOT NULL AUTO_INCREMENT,
    cumplimiento_documento_version_id INT NOT NULL,
    pregunta VARCHAR(1000) NOT NULL,
    orden_visualizacion INT NOT NULL DEFAULT 1,
    estado_tipo_id INT NOT NULL DEFAULT 2,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_preguntas_version (cumplimiento_documento_version_id),
    CONSTRAINT fk_preguntas_version
        FOREIGN KEY (cumplimiento_documento_version_id)
        REFERENCES cumplimiento_documento_versiones(id),
    CONSTRAINT fk_preguntas_estado
        FOREIGN KEY (estado_tipo_id)
        REFERENCES estado_tipos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

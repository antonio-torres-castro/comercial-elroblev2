CREATE TABLE cumplimiento_documento_versiones (
    id INT NOT NULL AUTO_INCREMENT,
    cumplimiento_documento_id INT NOT NULL,
    version VARCHAR(50) NOT NULL,
    titulo VARCHAR(250) NOT NULL,
    resumen VARCHAR(1000) NULL,
    contenido_html LONGTEXT NOT NULL,
    contenido_markdown LONGTEXT NULL,
    hash_documento VARCHAR(255) NULL,
    publicado TINYINT NOT NULL DEFAULT 0,
    fecha_publicacion DATETIME NULL,
    fecha_inicio_vigencia DATE NULL,
    fecha_fin_vigencia DATE NULL,
    creado_por_usuario_id INT NULL,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_documento_version (cumplimiento_documento_id,version),
    KEY idx_documento_version_publicado (publicado),
    CONSTRAINT fk_doc_version_documento
        FOREIGN KEY (cumplimiento_documento_id)
        REFERENCES cumplimiento_documentos(id),
    CONSTRAINT fk_doc_version_usuario
        FOREIGN KEY (creado_por_usuario_id)
        REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


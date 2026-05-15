CREATE TABLE cumplimiento_documentos (
    id INT NOT NULL AUTO_INCREMENT,
    proveedor_id INT NOT NULL,
    nombre VARCHAR(250) NOT NULL,
    codigo VARCHAR(100) NULL,
    descripcion VARCHAR(500) NULL,
    requiere_evaluacion TINYINT NOT NULL DEFAULT 1,
    puntaje_minimo DECIMAL(5,2) NOT NULL DEFAULT 80.00,
    cantidad_preguntas INT NOT NULL DEFAULT 5,
    vigencia_dias INT NOT NULL DEFAULT 365,
    estado_tipo_id INT NOT NULL DEFAULT 2,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documento_proveedor (proveedor_id),
    KEY idx_documento_estado (estado_tipo_id),
    CONSTRAINT fk_documento_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    CONSTRAINT fk_documento_estado FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
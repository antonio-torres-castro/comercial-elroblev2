DROP table if exists servicio_tipos;

CREATE TABLE IF NOT EXISTS servicio_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,

    servicio_categoria_id INT NULL,

    codigo VARCHAR(50) NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,

    color VARCHAR(20) NULL,

    requiere_aprobacion_cliente TINYINT DEFAULT 1,
    requiere_firma_cliente TINYINT DEFAULT 1,

    genera_proyecto_servicio TINYINT DEFAULT 1,

    duracion_estimada_dias INT NULL,

    estado_tipo_id INT NULL,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_servicio_categoria
        FOREIGN KEY (servicio_categoria_id)
        REFERENCES servicio_categorias(id),

    CONSTRAINT fk_estado_tipo
        FOREIGN KEY (estado_tipo_id)
        REFERENCES estado_tipos(id)
);
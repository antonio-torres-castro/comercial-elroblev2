Drop table if exists servicios;

CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,

    proveedor_id INT NOT NULL,
    servicio_tipo_id INT NOT NULL,

    codigo VARCHAR(50) NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,

    activo TINYINT DEFAULT 1,
    
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (servicio_tipo_id)
        REFERENCES servicio_tipos(id)
);
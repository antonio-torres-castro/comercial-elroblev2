Drop table if exists servicios_detalle_insumos;

CREATE TABLE IF NOT EXISTS servicios_detalle_insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_detalle_id INT NOT NULL,

    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    cantidad DECIMAL(10,2) DEFAULT 1,
    unidad_medida VARCHAR(50) NULL,
    obligatorio TINYINT DEFAULT 1,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_detalle_id)
        REFERENCES servicios_detalle(id)
);
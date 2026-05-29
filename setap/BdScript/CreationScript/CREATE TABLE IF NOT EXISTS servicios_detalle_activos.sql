DROP table if exists servicios_detalle_activos;

CREATE TABLE IF NOT EXISTS servicios_detalle_activos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_detalle_id INT NOT NULL,

    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    cantidad INT DEFAULT 1,
    obligatorio TINYINT DEFAULT 1,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servicio_detalle_id)
        REFERENCES servicios_detalle(id)
);
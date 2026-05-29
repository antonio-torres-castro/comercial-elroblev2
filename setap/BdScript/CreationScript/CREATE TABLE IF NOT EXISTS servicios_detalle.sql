Drop table if exists servicios_detalle;

CREATE TABLE IF NOT EXISTS servicios_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,

    servicio_version_id INT NOT NULL,

    proveedor_proceso_id INT NOT NULL,

    orden_ejecucion INT DEFAULT 1,

    dias_desde_inicio INT DEFAULT 0,

    obligatorio TINYINT DEFAULT 1,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (servicio_version_id)
        REFERENCES servicios_versiones(id),

    FOREIGN KEY (proveedor_proceso_id)
        REFERENCES proveedor_procesos(id)
);
Drop table if exists servicios_versiones;

CREATE TABLE IF NOT EXISTS servicios_versiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    servicio_id INT NOT NULL,
    version INT NOT NULL,
    
    nombre_version VARCHAR(150) NULL,
    descripcion TEXT NULL,
    precio_base DECIMAL(12,2) NULL,
    tiempo_estimado_dias INT NULL,

    vigente_desde DATE NULL,
    vigente_hasta DATE NULL,

    activo TINYINT DEFAULT 1,
	ind_version_actual TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (servicio_id)
        REFERENCES servicios(id)
);
Drop table if exists servicios_planificados;

CREATE TABLE IF NOT EXISTS servicios_planificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    cliente_id INT NOT NULL,
    servicio_version_id INT NOT NULL,
    
    version_nombre_snapshot VARCHAR(150) NULL,

    nombre VARCHAR(200) NULL,
    descripcion TEXT NULL,

    fecha_inicio DATE NOT NULL,
    fecha_termino_estimada DATE NOT NULL,
    fecha_termino_real DATE NULL,

    porcentaje_avance DECIMAL(5,2) DEFAULT 0,

    estado_id INT NULL,

    presupuesto_id INT NULL,

    observaciones TEXT NULL,
    
    estado_operacional_id INT NULL,
    
    usuario_creacion_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	fecha_generacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (servicio_version_id)   REFERENCES servicios_versiones(id),
    FOREIGN KEY (usuario_creacion_id)   REFERENCES usuarios(id),
    FOREIGN KEY (estado_operacional_id) REFERENCES estado_tipos(id)
);
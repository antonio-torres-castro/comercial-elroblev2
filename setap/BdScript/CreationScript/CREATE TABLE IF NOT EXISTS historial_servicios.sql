Drop table if exists historial_servicios;

CREATE TABLE IF NOT EXISTS historial_servicios (
    id INT NOT NULL AUTO_INCREMENT,
    servicio_planificado_id INT NOT NULL,
    usuario_id INT NOT NULL,

    accion VARCHAR(100) NOT NULL,

    descripcion TEXT NULL,
    
    estado_anterior_id INT NULL,
    estado_nuevo_id INT NULL,

    fecha_evento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    ip_origen VARCHAR(45) NULL,

    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,

    PRIMARY KEY (id),

    KEY fk_hs_servicio_planificado_idx (servicio_planificado_id),
    KEY fk_hs_usuario_idx (usuario_id),

    CONSTRAINT fk_hs_servicio_planificado
        FOREIGN KEY (servicio_planificado_id)
        REFERENCES servicios_planificados(id),

    CONSTRAINT fk_hs_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_0900_ai_ci;
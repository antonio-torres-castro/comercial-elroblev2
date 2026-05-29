Drop table if exists servicios_planificados_proyectos;

CREATE TABLE IF NOT EXISTS servicios_planificados_proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servicio_planificado_id INT NOT NULL,
    proyecto_id INT NOT NULL,

    orden INT DEFAULT 1,

    fecha_inicio DATE NOT NULL,
    fecha_termino DATE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (servicio_planificado_id)
        REFERENCES servicios_planificados(id),

    FOREIGN KEY (proyecto_id)
        REFERENCES proyectos(id)
);
Drop table if exists servicios_planificados_tareas;

CREATE TABLE IF NOT EXISTS servicios_planificados_tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,

    servicio_planificado_id INT NOT NULL,

    proyecto_tarea_id INT NOT NULL,
    orden_visualizacion INT DEFAULT 1,
	ind_replanificada TINYINT DEFAULT 0,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    fecha_programada_original DATE NULL,

    FOREIGN KEY (servicio_planificado_id)
        REFERENCES servicios_planificados(id),

    FOREIGN KEY (proyecto_tarea_id)
        REFERENCES proyecto_tareas(id)
);
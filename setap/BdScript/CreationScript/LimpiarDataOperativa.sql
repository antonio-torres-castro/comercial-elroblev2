-- ------------------------- --
-- Limpieza datos operativos --
-- ------------------------- --
DROP TABLE IF EXISTS tarea_fotos;
DROP TABLE IF EXISTS historial_tareas;
DROP TABLE IF EXISTS proyecto_feriados;
DROP TABLE IF EXISTS proyecto_tareas;
DROP TABLE IF exists tareas;

CREATE TABLE tareas (
  id int NOT NULL AUTO_INCREMENT,
  nombre varchar(150) NOT NULL,
  descripcion text NOT NULL,
  estado_tipo_id int NOT NULL DEFAULT '0',
  fecha_Creado timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY nombre (nombre),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT tareas_ibfk_1 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE proyecto_tareas (
  id int NOT NULL AUTO_INCREMENT,
  proyecto_id int NOT NULL,
  tarea_id int NOT NULL,
  planificador_id int NOT NULL,
  ejecutor_id int DEFAULT NULL,
  supervisor_id int DEFAULT NULL,
  fecha_inicio datetime NOT NULL,
  duracion_horas decimal(4,2) NOT NULL,
  fecha_fin datetime NOT NULL,
  prioridad int DEFAULT '0',
  estado_tipo_id int NOT NULL,
  fecha_Creado timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY proyecto_id (proyecto_id,tarea_id,fecha_inicio),
  KEY tarea_id (tarea_id),
  KEY planificador_id (planificador_id),
  KEY ejecutor_id (ejecutor_id),
  KEY supervisor_id (supervisor_id),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT proyecto_tareas_ibfk_1 FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT proyecto_tareas_ibfk_2 FOREIGN KEY (tarea_id) REFERENCES tareas (id),
  CONSTRAINT proyecto_tareas_ibfk_3 FOREIGN KEY (planificador_id) REFERENCES usuarios (id),
  CONSTRAINT proyecto_tareas_ibfk_4 FOREIGN KEY (ejecutor_id) REFERENCES usuarios (id),
  CONSTRAINT proyecto_tareas_ibfk_5 FOREIGN KEY (supervisor_id) REFERENCES usuarios (id),
  CONSTRAINT proyecto_tareas_ibfk_6 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE proyecto_feriados (
  id int NOT NULL AUTO_INCREMENT,
  proyecto_id int NOT NULL,
  fecha date NOT NULL,
  tipo_feriado enum('especifico','recurrente') DEFAULT NULL,
  ind_irrenunciable int DEFAULT '0',
  observaciones varchar(100) DEFAULT NULL,
  estado_tipo_id int NOT NULL DEFAULT '0',
  created_at timestamp NULL DEFAULT NULL,
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY proyecto_id (proyecto_id,fecha),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT proyecto_feriados_ibfk_1 FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT proyecto_feriados_ibfk_2 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE historial_tareas (
  id int NOT NULL AUTO_INCREMENT,
  proyecto_tarea_id int NOT NULL,
  usuario_id int NOT NULL,
  supervisor_id int DEFAULT NULL,
  contraparte_id int DEFAULT NULL,
  fecha_evento datetime(3) NOT NULL,
  comentario text,
  estado_tipo_anterior int DEFAULT NULL,
  estado_tipo_nuevo int DEFAULT NULL,
  PRIMARY KEY (id),
  KEY proyecto_tarea_id (proyecto_tarea_id),
  KEY usuario_id (usuario_id),
  KEY supervisor_id (supervisor_id),
  KEY contraparte_id (contraparte_id),
  KEY estado_tipo_anterior (estado_tipo_anterior),
  KEY estado_tipo_nuevo (estado_tipo_nuevo),
  CONSTRAINT historial_tareas_ibfk_1 FOREIGN KEY (proyecto_tarea_id) REFERENCES proyecto_tareas (id),
  CONSTRAINT historial_tareas_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
  CONSTRAINT historial_tareas_ibfk_3 FOREIGN KEY (supervisor_id) REFERENCES usuarios (id),
  CONSTRAINT historial_tareas_ibfk_4 FOREIGN KEY (contraparte_id) REFERENCES cliente_contrapartes (id),
  CONSTRAINT historial_tareas_ibfk_5 FOREIGN KEY (estado_tipo_anterior) REFERENCES estado_tipos (id),
  CONSTRAINT historial_tareas_ibfk_6 FOREIGN KEY (estado_tipo_nuevo) REFERENCES estado_tipos (id)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE tarea_fotos (
  id int NOT NULL AUTO_INCREMENT,
  historial_tarea_id int NOT NULL,
  url_foto varchar(255) NOT NULL,
  fecha_Creado timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  estado_tipo_id int NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY historial_tarea_id (historial_tarea_id),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT tarea_fotos_ibfk_1 FOREIGN KEY (historial_tarea_id) REFERENCES historial_tareas (id),
  CONSTRAINT tarea_fotos_ibfk_2 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

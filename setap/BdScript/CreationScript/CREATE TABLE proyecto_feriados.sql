DROP TABLE proyecto_feriados;

CREATE TABLE proyecto_feriados (
  id int NOT NULL AUTO_INCREMENT,
  proyecto_id int NOT NULL,
  fecha date NOT NULL,
  -- Campos adicionales sugeridos:
  tipo_feriado ENUM('especifico', 'recurrente'), -- Para distinguir origen
  ind_irrenunciable int default 0, -- 0 renunciable, 1 irrenunciable
  observaciones VARCHAR(100), -- Descripción del feriado
  estado_tipo_id int NOT NULL DEFAULT '0',
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY proyecto_id (proyecto_id,fecha),
  KEY estado_tipo_id (estado_tipo_id),
  CONSTRAINT proyecto_feriados_ibfk_1 FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT proyecto_feriados_ibfk_2 FOREIGN KEY (estado_tipo_id) REFERENCES estado_tipos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


    
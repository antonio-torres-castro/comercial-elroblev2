CREATE TABLE proyecto_usuarios_grupo_disponibilidad (
  id int NOT NULL AUTO_INCREMENT,
  proyecto_id int NOT NULL,
  grupo_id int NOT NULL, -- Al principio solo sera con valor 4=ejecutor, pero luego que madure el proceso sera tambien 2=planner y 3=supervisor
  usuario_id int NOT NULL,
  fecha date NOT NULL,
  hh decimal(10,0) DEFAULT '0',
  tipo_fecha_id int NOT NULL, -- 1=laboral, 2=vacacion, 3=licencia, 4=permiso, 5=ausencia, 6=feriado laborable
  PRIMARY KEY (id),
  KEY fk_pugd_proyecto_id_idx (proyecto_id),
  KEY fk_pugd_grupo_id_idx (grupo_id),
  KEY fk_pugd_usuario_id_idx (usuario_id),
  KEY fk_pugd_tipo_fecha_id_idx (tipo_fecha_id),
  CONSTRAINT fk_pugd_tipo_fecha_id FOREIGN KEY (tipo_fecha_id) REFERENCES tipos_fecha (id),
  CONSTRAINT fk_pugd_grupo_id FOREIGN KEY (grupo_id) REFERENCES grupo_tipos (id),
  CONSTRAINT fk_pugd_proyecto_id FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT fk_pugd_usuario_id FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Los usuarios disponibles para trabajar en el proyecto';

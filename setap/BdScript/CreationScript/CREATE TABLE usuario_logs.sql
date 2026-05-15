CREATE TABLE usuario_logs (
  id int NOT NULL AUTO_INCREMENT,
  usuario_id int DEFAULT NULL,
  accion_id int NOT NULL DEFAULT '1',
  fecha timestamp NULL DEFAULT NULL,
  IP varchar(45) DEFAULT NULL,
  tabla_referencia_id int DEFAULT NULL,
  referencia_id int DEFAULT NULL,
  PRIMARY KEY (id),
  KEY fk_acciones_idx (accion_id),
  KEY fk_tablas_referencia_idx (tabla_referencia_id),
  CONSTRAINT fk_acciones FOREIGN KEY (accion_id) REFERENCES acciones (id),
  CONSTRAINT fk_tablas_referencia FOREIGN KEY (tabla_referencia_id) REFERENCES tablas_referencia (id)
) ENGINE=InnoDB AUTO_INCREMENT=373 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Registro de login=1 y logout=2';
